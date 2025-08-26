<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{


    protected $fillable = [
        'category',
        'subcategory',
        'description',
        'amount',
        'payment_method',
        'vendor_name',
        'receipt_number',
        'expense_date',
        'status',
        'notes',
        'receipt_image',
        'user_id',
        'approved_by',
        'approved_at',
        'tax_amount',
        'tax_rate',
        'is_recurring',
        'recurring_frequency',
        'next_due_date'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'approved_at' => 'datetime',
        'next_due_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_recurring' => 'boolean'
    ];

    protected $dates = [
        'expense_date',
        'approved_at',
        'next_due_date',
        'deleted_at'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    // Accessors
    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->tax_amount;
    }

    public function getFormattedAmountAttribute()
    {
        return 'KES ' . number_format($this->amount, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'KES ' . number_format($this->total_amount, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => ['class' => 'warning', 'text' => 'Pending'],
            'approved' => ['class' => 'success', 'text' => 'Approved'],
            'rejected' => ['class' => 'danger', 'text' => 'Rejected'],
            'paid' => ['class' => 'info', 'text' => 'Paid']
        ];

        return $badges[$this->status] ?? ['class' => 'secondary', 'text' => 'Unknown'];
    }

    // Methods
    public function approve($userId = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    public function markAsPaid()
    {
        $this->update(['status' => 'paid']);
    }

    public function generateNextRecurrence()
    {
        if (!$this->is_recurring || !$this->next_due_date) {
            return null;
        }

        $frequencies = [
            'weekly' => '+1 week',
            'monthly' => '+1 month',
            'quarterly' => '+3 months',
            'yearly' => '+1 year'
        ];

        $interval = $frequencies[$this->recurring_frequency] ?? '+1 month';
        
        return $this->replicate()->fill([
            'expense_date' => $this->next_due_date,
            'next_due_date' => date('Y-m-d', strtotime($interval, strtotime($this->next_due_date))),
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'receipt_number' => null
        ]);
    }

    // Static methods
    public static function getCategories()
    {
        return [
            'animal_feed' => [
                'label' => 'Animal Feed & Nutrition',
                'subcategories' => [
                    'cattle_feed' => 'Cattle Feed & Supplements',
                    'poultry_feed' => 'Poultry Feed & Layers Mash',
                    'pig_feed' => 'Pig Feed & Concentrates',
                    'sheep_goat_feed' => 'Sheep & Goat Feed',
                    'fish_feed' => 'Fish Feed & Aquaculture',
                    'pet_food' => 'Pet Food & Treats',
                    'minerals_vitamins' => 'Minerals & Vitamins',
                    'feed_additives' => 'Feed Additives & Premixes'
                ]
            ],
            'veterinary_supplies' => [
                'label' => 'Veterinary Supplies & Medicine',
                'subcategories' => [
                    'vaccines' => 'Vaccines & Immunizations',
                    'antibiotics' => 'Antibiotics & Antimicrobials',
                    'dewormers' => 'Dewormers & Antiparasitics',
                    'antiseptics' => 'Antiseptics & Disinfectants',
                    'syringes_needles' => 'Syringes, Needles & Equipment',
                    'bandages_dressings' => 'Bandages & Wound Dressings',
                    'diagnostic_kits' => 'Diagnostic Kits & Tests',
                    'hormones' => 'Hormones & Reproductive Products'
                ]
            ],
            'farm_equipment' => [
                'label' => 'Farm Equipment & Tools',
                'subcategories' => [
                    'hand_tools' => 'Hand Tools & Small Equipment',
                    'machinery' => 'Farm Machinery & Tractors',
                    'irrigation' => 'Irrigation Systems & Water Equipment',
                    'fencing' => 'Fencing Materials & Gates',
                    'storage_equipment' => 'Storage & Handling Equipment',
                    'milking_equipment' => 'Milking Equipment & Dairy Supplies',
                    'incubators' => 'Incubators & Hatching Equipment',
                    'maintenance_repairs' => 'Equipment Maintenance & Repairs'
                ]
            ],
            'seeds_fertilizers' => [
                'label' => 'Seeds, Fertilizers & Agro Inputs',
                'subcategories' => [
                    'crop_seeds' => 'Crop Seeds & Varieties',
                    'pasture_seeds' => 'Pasture & Fodder Seeds',
                    'vegetable_seeds' => 'Vegetable & Garden Seeds',
                    'organic_fertilizer' => 'Organic Fertilizers & Compost',
                    'chemical_fertilizer' => 'Chemical Fertilizers & NPK',
                    'pesticides' => 'Pesticides & Fungicides',
                    'herbicides' => 'Herbicides & Weed Control',
                    'soil_amendments' => 'Soil Amendments & Conditioners'
                ]
            ],
            'animal_health' => [
                'label' => 'Animal Health & Care',
                'subcategories' => [
                    'health_supplements' => 'Health Supplements & Tonics',
                    'grooming_supplies' => 'Grooming & Care Supplies',
                    'breeding_supplies' => 'Breeding & Artificial Insemination',
                    'hoof_care' => 'Hoof Care & Trimming Supplies',
                    'first_aid' => 'First Aid & Emergency Supplies',
                    'quarantine_supplies' => 'Quarantine & Isolation Supplies'
                ]
            ],
            'operational' => [
                'label' => 'Operational Expenses',
                'subcategories' => [
                    'rent' => 'Shop Rent & Property',
                    'utilities' => 'Electricity, Water & Internet',
                    'fuel' => 'Fuel & Transportation',
                    'insurance' => 'Business & Product Insurance',
                    'licenses' => 'Business Licenses & Permits',
                    'security' => 'Security & Surveillance'
                ]
            ],
            'staff' => [
                'label' => 'Staff & Personnel',
                'subcategories' => [
                    'salaries' => 'Salaries & Wages',
                    'benefits' => 'Employee Benefits & Medical',
                    'training' => 'Agricultural Training & Education',
                    'recruitment' => 'Recruitment & HR',
                    'uniforms' => 'Uniforms & Work Gear',
                    'safety_equipment' => 'Safety Equipment & PPE'
                ]
            ],
            'inventory' => [
                'label' => 'Inventory & Storage',
                'subcategories' => [
                    'storage_facility' => 'Storage Facility & Warehousing',
                    'packaging' => 'Packaging & Bags',
                    'labels_tags' => 'Labels & Product Tags',
                    'inventory_systems' => 'Inventory Management Systems',
                    'cold_storage' => 'Cold Storage & Refrigeration',
                    'pest_control' => 'Pest Control & Fumigation',
                    'shelving_displays' => 'Shelving & Product Displays'
                ]
            ],
            'marketing' => [
                'label' => 'Marketing & Customer Education',
                'subcategories' => [
                    'advertising' => 'Advertising & Promotions',
                    'farmer_education' => 'Farmer Education & Workshops',
                    'demonstrations' => 'Product Demonstrations',
                    'trade_shows' => 'Agricultural Trade Shows',
                    'brochures_flyers' => 'Brochures & Educational Materials',
                    'field_days' => 'Field Days & Farm Visits',
                    'radio_tv_ads' => 'Radio & TV Advertisements'
                ]
            ],
            'professional_services' => [
                'label' => 'Professional Services',
                'subcategories' => [
                    'veterinary_consultation' => 'Veterinary Consultation',
                    'agronomist' => 'Agronomist & Extension Services',
                    'accounting' => 'Accounting & Bookkeeping',
                    'legal' => 'Legal Services',
                    'certification' => 'Product Certification & Testing',
                    'regulatory_compliance' => 'Regulatory Compliance'
                ]
            ],
            'technology' => [
                'label' => 'Technology & Systems',
                'subcategories' => [
                    'pos_system' => 'POS System & Software',
                    'mobile_apps' => 'Mobile Apps & Digital Tools',
                    'weighing_scales' => 'Digital Weighing Scales',
                    'computers_tablets' => 'Computers & Tablets',
                    'internet_phone' => 'Internet & Phone Services',
                    'software_licenses' => 'Software Licenses & Updates'
                ]
            ],
            'others' => [
                'label' => 'Others',
                'subcategories' => [
                    'miscellaneous' => 'Miscellaneous Expenses',
                    'emergency' => 'Emergency Expenses',
                    'donations' => 'Community Donations',
                    'farmer_support' => 'Farmer Support Programs',
                    'research_development' => 'Research & Development',
                    'environmental' => 'Environmental & Sustainability',
                    'other' => 'Other Unspecified Expenses'
                ]
            ]
        ];
    }

    public static function getPaymentMethods()
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'mobile_money' => 'Mobile Money (M-Pesa)',
            'cheque' => 'Cheque',
            'online_payment' => 'Online Payment'
        ];
    }

    public static function getRecurringFrequencies()
    {
        return [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly'
        ];
    }
}
