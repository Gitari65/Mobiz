<?php
    namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // --- STOCK TRANSFER BETWEEN WAREHOUSES (Admin Only) ---

    public function transferStock(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user->load('role');
        $roleName = strtolower($user->role->name ?? '');
        if (!in_array($roleName, ['admin', 'administrator', 'superuser'])) {
            return response()->json(['error' => 'Only administrators can transfer stock'], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'destination_type' => 'required|string|in:warehouse,supplier_return,write_off,adjustment_out',
            'reason' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'external_target' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $product = \App\Models\Product::findOrFail($validated['product_id']);
        $fromWarehouse = \App\Models\Warehouse::findOrFail($validated['from_warehouse_id']);

        // Verify product belongs to source warehouse
        if ($product->warehouse_id != $fromWarehouse->id) {
            return response()->json(['error' => 'Product not stored in source warehouse'], 400);
        }

        // Check stock availability
        if ($product->stock_quantity < $validated['quantity']) {
            return response()->json(['error' => 'Insufficient stock in source warehouse'], 400);
        }

        $destinationType = $validated['destination_type'];
        $quantity = $validated['quantity'];

        if ($destinationType === 'warehouse') {
            if (empty($validated['to_warehouse_id'])) {
                return response()->json(['error' => 'Destination warehouse is required'], 422);
            }

            $toWarehouse = \App\Models\Warehouse::findOrFail($validated['to_warehouse_id']);

            // Deduct from source
            $product->stock_quantity -= $quantity;
            $product->save();

            // Add to destination warehouse (create or update product record)
            $destProduct = \App\Models\Product::where('warehouse_id', $toWarehouse->id)
                ->where('sku', $product->sku)
                ->first();

            if ($destProduct) {
                $destProduct->stock_quantity += $quantity;
                $destProduct->save();
            } else {
                $newProduct = $product->replicate();
                $newProduct->warehouse_id = $toWarehouse->id;
                $newProduct->stock_quantity = $quantity;
                $newProduct->company_id = $user->company_id;
                $newProduct->created_by = $user->id;
                $newProduct->updated_by = $user->id;
                $newProduct->save();
            }

            $toWarehouseId = $toWarehouse->id;
        } else {
            // Outbound move (return, write-off, adjustment_out)
            $product->stock_quantity -= $quantity;
            $product->save();
            $toWarehouseId = null;
        }

        // Log the transfer
        \App\Models\WarehouseTransfer::create([
            'transfer_number' => 'TRF-' . strtoupper(substr(uniqid() . bin2hex(random_bytes(2)), -10)),
            'product_id' => $product->id,
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouseId,
            'quantity' => $quantity,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'transfer_type' => $destinationType,
            'reason' => $validated['reason'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'external_target' => $validated['external_target'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        $message = $destinationType === 'warehouse' ? 'Stock transferred successfully' : 'Stock adjusted successfully';
        return response()->json(['message' => $message]);
    }
    // --- BUSINESS CATEGORIES (Admin Only) ---

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category = \App\Models\BusinessCategory::create([
            'name' => $validated['name'],
            'user_id' => $request->user()->id,
        ]);
        return response()->json(['message' => 'Category created', 'category' => $category], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = \App\Models\BusinessCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category->update($validated);
        return response()->json(['message' => 'Category updated', 'category' => $category]);
    }

    public function destroyCategory($id)
    {
        $category = \App\Models\BusinessCategory::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }

    // --- WAREHOUSES (Admin Only) ---

    public function storeWarehouse(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
        ]);
        $warehouse = \App\Models\Warehouse::create([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'user_id' => $request->user()->id,
        ]);
        return response()->json(['message' => 'Warehouse created', 'warehouse' => $warehouse], 201);
    }

    public function updateWarehouse(Request $request, $id)
    {
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
        ]);
        $warehouse->update($validated);
        return response()->json(['message' => 'Warehouse updated', 'warehouse' => $warehouse]);
    }

    public function destroyWarehouse($id)
    {
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted']);
    }
    // Fetch all products
    public function index()
    {
        try {
            // Simple query first to test
            $products = Product::query()->get();
            
            // Try to load relationships if basic query works
            $products->load([
                'warehouse', 
                'uom', 
                'creator', 
                'editor', 
                'company'
            ]);
            
            // Load prices separately to avoid errors
            try {
                $products->load('prices.priceGroup');
            } catch (\Exception $priceError) {
                Log::warning('Could not load prices: ' . $priceError->getMessage());
            }
            
            return response()->json($products, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    // Create single product
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Enhanced validation for all product fields
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'sku' => 'nullable|string|max:100|unique:products,sku',
                'category' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'cost_price' => 'nullable|numeric|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'description' => 'nullable|string|max:1000',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'uom_id' => 'nullable|exists:u_o_m_s,id',
                'prices' => 'nullable|array',
                'prices.*' => 'numeric|min:0'
            ]);

            // Store price group prices
            $pricesByGroup = $validated['prices'] ?? [];
            unset($validated['prices']);

            // Auto-generate SKU if not provided
            if (empty($validated['sku'])) {
                $validated['sku'] = $this->generateSKU($validated['name']);
            }

            // Add company_id and tracking fields
            $validated['company_id'] = $user->company_id;
            $validated['created_by'] = $user->id;
            $validated['updated_by'] = $user->id;

            // Create the product
            $product = Product::create($validated);

            // Save price group specific prices if provided
            if (!empty($pricesByGroup)) {
                foreach ($pricesByGroup as $priceGroupId => $price) {
                    if ($price !== null && $price !== '') {
                        \App\Models\ProductPrice::create([
                            'product_id' => $product->id,
                            'price_group_id' => $priceGroupId,
                            'price' => $price
                        ]);
                    }
                }
            }

            // Load relationships for response
            $product->load(['warehouse', 'uom', 'creator', 'editor', 'company', 'prices.priceGroup']);

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create product: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create product'], 500);
        }
    }

    // Create multiple products (bulk)
    public function storeBulk(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Validate that products is an array
            $request->validate([
                'products' => 'required|array|min:1|max:100',
                'products.*.name' => 'required|string|max:255',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.stock_quantity' => 'required|integer|min:0',
                'products.*.sku' => 'nullable|string|max:100',
                'products.*.category' => 'nullable|string|max:100',
                'products.*.brand' => 'nullable|string|max:100',
                'products.*.cost_price' => 'nullable|numeric|min:0',
                'products.*.low_stock_threshold' => 'nullable|integer|min:0',
                'products.*.description' => 'nullable|string|max:1000',
                'products.*.warehouse_id' => 'nullable|exists:warehouses,id',
                'products.*.uom_id' => 'nullable|exists:u_o_m_s,id',
                'products.*.prices' => 'nullable|array',
                'products.*.prices.*' => 'numeric|min:0'
            ]);

            $products = $request->input('products');
            $createdProducts = [];
            $errors = [];

            foreach ($products as $index => $productData) {
                try {
                    // Extract prices before creating product
                    $pricesByGroup = isset($productData['prices']) ? $productData['prices'] : [];
                    unset($productData['prices']);
                    
                    // Auto-generate SKU if not provided
                    if (empty($productData['sku'])) {
                        $productData['sku'] = $this->generateSKU($productData['name']);
                    }

                    // Check for duplicate SKU
                    if (Product::where('sku', $productData['sku'])->exists()) {
                        $productData['sku'] = $this->generateSKU($productData['name'], true);
                    }

                    // Add tracking fields
                    $productData['company_id'] = $user->company_id;
                    $productData['created_by'] = $user->id;
                    $productData['updated_by'] = $user->id;

                    $product = Product::create($productData);
                    
                    // Save price group specific prices if provided
                    if (!empty($pricesByGroup)) {
                        foreach ($pricesByGroup as $priceGroupId => $price) {
                            if ($price !== null && $price !== '') {
                                \App\Models\ProductPrice::create([
                                    'product_id' => $product->id,
                                    'price_group_id' => $priceGroupId,
                                    'price' => $price
                                ]);
                            }
                        }
                    }
                    
                    // Load relationships
                    $product->load(['warehouse', 'uom', 'creator', 'editor', 'company', 'prices.priceGroup']);
                    $createdProducts[] = $product;

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'product' => $productData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'message' => 'Bulk product creation completed',
                'created_count' => count($createdProducts),
                'error_count' => count($errors),
                'products' => $createdProducts,
                'errors' => $errors
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Bulk validation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create bulk products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create bulk products'], 500);
        }
    }

    // Import products from CSV
    public function importCSV(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Validate CSV file
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            ]);

            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->path()));
            
            // Remove header row
            $header = array_shift($csvData);
            
            // Validate header format
            $expectedHeaders = ['name', 'price', 'stock_quantity', 'sku', 'category', 'brand', 'cost_price', 'low_stock_threshold', 'description'];
            $headerMap = array_flip(array_map('trim', $header));

            $createdProducts = [];
            $errors = [];

            foreach ($csvData as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Map CSV data to product fields
                    $productData = [
                        'name' => $row[$headerMap['name']] ?? '',
                        'price' => (float)($row[$headerMap['price']] ?? 0),
                        'stock_quantity' => (int)($row[$headerMap['stock_quantity']] ?? 0),
                        'sku' => $row[$headerMap['sku']] ?? '',
                        'category' => $row[$headerMap['category']] ?? null,
                        'brand' => $row[$headerMap['brand']] ?? null,
                        'cost_price' => !empty($row[$headerMap['cost_price']] ?? '') ? (float)$row[$headerMap['cost_price']] : null,
                        'low_stock_threshold' => !empty($row[$headerMap['low_stock_threshold']] ?? '') ? (int)$row[$headerMap['low_stock_threshold']] : null,
                        'description' => $row[$headerMap['description']] ?? null,
                    ];

                    // Validate required fields
                    if (empty($productData['name']) || $productData['price'] <= 0 || $productData['stock_quantity'] < 0) {
                        throw new \Exception('Missing required fields or invalid values');
                    }

                    // Auto-generate SKU if not provided
                    if (empty($productData['sku'])) {
                        $productData['sku'] = $this->generateSKU($productData['name']);
                    }

                    // Check for duplicate SKU
                    if (Product::where('sku', $productData['sku'])->exists()) {
                        $productData['sku'] = $this->generateSKU($productData['name'], true);
                    }

                    // Add tracking fields
                    $productData['company_id'] = $user->company_id;
                    $productData['created_by'] = $user->id;
                    $productData['updated_by'] = $user->id;

                    $product = Product::create($productData);
                    // Load relationships
                    $product->load(['warehouse', 'uom', 'creator', 'editor', 'company', 'prices.priceGroup']);
                    $createdProducts[] = $product;

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 2, // +2 because index starts at 0 and we removed header
                        'product' => $productData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'message' => 'CSV import completed',
                'created_count' => count($createdProducts),
                'error_count' => count($errors),
                'products' => $createdProducts,
                'errors' => $errors
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('CSV validation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid CSV file', 'details' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Failed to import CSV: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to import CSV'], 500);
        }
    }

    // Helper method to generate SKU
    private function generateSKU($productName, $forceUnique = false)
    {
        // Create base SKU from product name
        $baseSku = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 6));
        
        if (strlen($baseSku) < 3) {
            $baseSku = str_pad($baseSku, 3, 'X');
        }

        $sku = $baseSku . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while (Product::where('sku', $sku)->exists() || $forceUnique) {
            $sku = $baseSku . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $forceUnique = false;
        }

        return $sku;
    }

    // Download CSV template for product import
    public function downloadCSVTemplate()
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
            ];

            $csvContent = "name,price,stock_quantity,sku,category,brand,cost_price,low_stock_threshold,description\n";
            $csvContent .= "DAP Fertilizer 50kg,3500.00,50,FERT-0001,fertilizers,Yara,2800.00,10,Di-ammonium phosphate fertilizer for enhanced crop growth\n";
            $csvContent .= "Roundup Herbicide 1L,1800.00,30,PEST-0001,pesticides,Monsanto,1200.00,5,Glyphosate-based systemic herbicide for weed control\n";
            $csvContent .= "Maize Hybrid Seeds 2kg,1200.00,100,SEED-0001,seeds,Pioneer,800.00,20,High-yield drought resistant hybrid maize variety\n";

            return response($csvContent, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to generate CSV template: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate CSV template'], 500);
        }
    }

    // Get product statistics
    public function getStatistics()
    {
        try {
            $stats = [
                'total_products' => Product::count(),
                'low_stock_products' => Product::lowStock()->count(),
                'out_of_stock_products' => Product::outOfStock()->count(),
                'total_inventory_value' => Product::sum('price'),
                'categories' => Product::distinct('category')->whereNotNull('category')->pluck('category'),
                'brands' => Product::distinct('brand')->whereNotNull('brand')->pluck('brand'),
            ];

            return response()->json($stats, 200);

        } catch (\Exception $e) {
            Log::error('Failed to get product statistics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get statistics'], 500);
        }
    }

    // Get out of stock products
    public function getOutOfStockProducts()
    {
        try {
            $cacheKey = 'out_of_stock_products_v1';
            $outOfStockProducts = \Cache::remember($cacheKey, now()->addSeconds(30), function () {
                return Product::where('stock_quantity', '<=', 0)
                    ->orderBy('updated_at', 'desc')
                    ->get(['id', 'name', 'sku', 'category', 'price', 'stock_quantity', 'updated_at']);
            });
            return response()->json($outOfStockProducts, 200);
        } catch (\Exception $e) {
            Log::error('Failed to get out of stock products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get out of stock products'], 500);
        }
    }

    // Get low stock products
    public function getLowStockProducts()
    {
        try {
            $lowStockProducts = Product::lowStock()
                ->orderBy('stock_quantity', 'asc')
                ->get(['id', 'name', 'sku', 'category', 'price', 'stock_quantity', 'low_stock_threshold', 'updated_at']);

            return response()->json($lowStockProducts, 200);

        } catch (\Exception $e) {
            Log::error('Failed to get low stock products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get low stock products'], 500);
        }
    }

    // Show specific product
    public function show($id)
    {
        try {
            $product = Product::with(['warehouse', 'uom', 'creator', 'editor', 'company', 'prices.priceGroup'])->findOrFail($id);
            return response()->json($product);
        } catch (\Exception $e) {
            Log::error("Error showing product with ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    // Update product
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $product = Product::findOrFail($id);

            // Check authorization - only admin and superuser can edit products
            $user->load('role');
            $roleName = strtolower($user->role->name ?? '');
            if (!in_array($roleName, ['admin', 'administrator', 'superuser'])) {
                return response()->json(['error' => 'Only administrators can edit products'], 403);
            }

            // Admin can only edit their company's products (unless superuser)
            if ($roleName !== 'superuser' && $product->company_id !== $user->company_id) {
                return response()->json(['error' => 'You can only edit products from your company'], 403);
            }

            // Enhanced validation for all product fields
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'stock_quantity' => 'sometimes|integer|min:0',
                'sku' => 'sometimes|string|max:100|unique:products,sku,' . $id,
                'category' => 'sometimes|nullable|string|max:100',
                'brand' => 'sometimes|nullable|string|max:100',
                'cost_price' => 'sometimes|nullable|numeric|min:0',
                'low_stock_threshold' => 'sometimes|nullable|integer|min:0',
                'description' => 'sometimes|nullable|string|max:1000',
                'warehouse_id' => 'sometimes|nullable|exists:warehouses,id',
                'uom_id' => 'sometimes|nullable|exists:u_o_m_s,id',
                'prices' => 'nullable|array',
                'prices.*' => 'numeric|min:0'
            ]);

            // Handle price group specific prices
            $pricesByGroup = $validated['prices'] ?? [];
            unset($validated['prices']);

            // Track who last updated the product
            $validated['updated_by'] = $user->id;

            $product->update($validated);

            // Update price group specific prices
            if (!empty($pricesByGroup)) {
                foreach ($pricesByGroup as $priceGroupId => $price) {
                    if ($price !== null && $price !== '') {
                        \App\Models\ProductPrice::updateOrCreate(
                            ['product_id' => $product->id, 'price_group_id' => $priceGroupId],
                            ['price' => $price]
                        );
                    }
                }
            }
            
            // Load relationships for response
            $product->load(['warehouse', 'uom', 'creator', 'editor', 'company', 'prices.priceGroup']);
            
            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error("Error updating product with ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update product'], 500);
        }
    }

    // Delete product
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting product with ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }

    /**
     * Get the price for a product in a specific price group
     */
    public function getPriceForGroup(Product $product, $groupId)
    {
        try {
            $price = $product->getPriceForGroup($groupId);
            return response()->json([
                'product_id' => $product->id,
                'price_group_id' => $groupId,
                'base_price' => $product->price,
                'final_price' => $price,
                'currency' => 'KES'
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting price for group: " . $e->getMessage());
            return response()->json(['error' => 'Failed to get price'], 500);
        }
    }

    /**
     * Get the price for a product for a specific customer
     */
    public function getPriceForCustomer(Product $product, $customerId)
    {
        try {
            $customer = \App\Models\Customer::findOrFail($customerId);
            $price = $product->getPriceForCustomer($customer);
            
            return response()->json([
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'price_group_id' => $customer->price_group_id,
                'price_group_name' => $customer->priceGroup->name ?? 'Standard',
                'base_price' => $product->price,
                'final_price' => $price,
                'currency' => 'KES'
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting price for customer: " . $e->getMessage());
            return response()->json(['error' => 'Failed to get price'], 500);
        }
    }

    // --- EMPTIES/RETURNABLES MANAGEMENT ---

    /**
     * Get all empties linked to a product
     */
    public function getEmpties($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $empties = $product->empties()
                ->select('products.id', 'products.name', 'products.sku', 'products.price')
                ->get()
                ->map(function ($empty) {
                    return [
                        'id' => $empty->id,
                        'name' => $empty->name,
                        'sku' => $empty->sku,
                        'price' => $empty->price,
                        'quantity' => $empty->pivot->quantity,
                        'deposit_amount' => $empty->pivot->deposit_amount,
                        'is_active' => $empty->pivot->is_active,
                    ];
                });

            return response()->json(['data' => $empties]);
        } catch (\Exception $e) {
            Log::error("Error getting empties for product {$productId}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch empties'], 500);
        }
    }

    /**
     * Link an empty/returnable to a product
     */
    public function linkEmpty(Request $request, $productId)
    {
        try {
            $validated = $request->validate([
                'empty_product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'deposit_amount' => 'nullable|numeric|min:0',
            ]);

            $product = Product::findOrFail($productId);
            
            // Prevent linking a product to itself
            if ($productId == $validated['empty_product_id']) {
                return response()->json(['error' => 'Cannot link a product to itself'], 400);
            }

            // Check if link already exists
            $existing = $product->empties()->where('empty_product_id', $validated['empty_product_id'])->first();
            if ($existing) {
                return response()->json(['error' => 'This empty is already linked to the product'], 400);
            }

            $product->empties()->attach($validated['empty_product_id'], [
                'quantity' => $validated['quantity'],
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Empty linked successfully',
                'data' => $this->getEmpties($productId)->getData()->data
            ]);
        } catch (\Exception $e) {
            Log::error("Error linking empty to product {$productId}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to link empty'], 500);
        }
    }

    /**
     * Update an empty link (quantity, deposit, etc.)
     */
    public function updateEmpty(Request $request, $productId, $emptyId)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
                'deposit_amount' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            $product = Product::findOrFail($productId);
            
            $product->empties()->updateExistingPivot($emptyId, [
                'quantity' => $validated['quantity'],
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'message' => 'Empty link updated successfully',
                'data' => $this->getEmpties($productId)->getData()->data
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating empty link for product {$productId}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update empty link'], 500);
        }
    }

    /**
     * Unlink an empty from a product
     */
    public function unlinkEmpty($productId, $emptyId)
    {
        try {
            $product = Product::findOrFail($productId);
            $product->empties()->detach($emptyId);

            return response()->json(['message' => 'Empty unlinked successfully']);
        } catch (\Exception $e) {
            Log::error("Error unlinking empty from product {$productId}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to unlink empty'], 500);
        }
    }

    /**
     * Get products that can be used as empties (excluding current product)
     */
    public function getAvailableEmpties($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $user = auth()->user();
            // Fallback to the product's company when the request is unauthenticated
            $companyId = $user ? $user->company_id : $product->company_id;

            // Get products from the same company (when known), excluding the current product
            $availableEmpties = Product::where('id', '!=', $productId)
                ->when($companyId, function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
                ->select('id', 'name', 'sku', 'price', 'category')
                ->orderBy('name')
                ->get();

            return response()->json(['data' => $availableEmpties]);
        } catch (\Exception $e) {
            Log::error("Error getting available empties: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch available empties'], 500);
        }
    }
}
