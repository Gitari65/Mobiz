<template>
  <div class="diagnostics-page">
    <header class="diag-header">
      <div>
        <h1>Request Diagnostics</h1>
        <p>Top slow endpoints from in-browser request metrics.</p>
      </div>
      <div class="diag-actions">
        <button class="btn" @click="refreshMetrics">Refresh</button>
        <button class="btn secondary" @click="toggleAutoRefresh">
          {{ autoRefresh ? 'Auto Refresh: ON' : 'Auto Refresh: OFF' }}
        </button>
      </div>
    </header>

    <section class="summary-grid">
      <article class="summary-card">
        <h3>Tracked Endpoints</h3>
        <p>{{ summary.endpoints }}</p>
      </article>
      <article class="summary-card">
        <h3>Total Requests</h3>
        <p>{{ summary.requests }}</p>
      </article>
      <article class="summary-card">
        <h3>Slowest Avg</h3>
        <p>{{ summary.slowestAvg }}</p>
      </article>
      <article class="summary-card">
        <h3>Worst Single</h3>
        <p>{{ summary.worstSingle }}</p>
      </article>
    </section>

    <section class="table-wrap">
      <div v-if="rows.length === 0" class="empty-state">
        <p>No metrics found yet. Use the app, then click Refresh.</p>
      </div>
      <table v-else class="diag-table">
        <thead>
          <tr>
            <th>Endpoint</th>
            <th>Requests</th>
            <th>Avg (ms)</th>
            <th>Max (ms)</th>
            <th>Total (ms)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.endpoint">
            <td class="endpoint">{{ row.endpoint }}</td>
            <td>{{ row.count }}</td>
            <td>{{ row.avgMs }}</td>
            <td :class="row.maxMs >= 1000 ? 'warn' : ''">{{ row.maxMs }}</td>
            <td>{{ row.totalMs }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const rows = ref([])
const autoRefresh = ref(true)
let timer = null

function refreshMetrics() {
  const metrics = window.__mobizRequestMetrics
  if (!(metrics instanceof Map)) {
    rows.value = []
    return
  }

  rows.value = Array.from(metrics.entries())
    .map(([endpoint, value]) => {
      const count = Number(value?.count || 0)
      const totalMs = Number(value?.totalMs || 0)
      const maxMs = Number(value?.maxMs || 0)
      const avgMs = count > 0 ? Math.round(totalMs / count) : 0

      return {
        endpoint,
        count,
        totalMs,
        maxMs,
        avgMs,
      }
    })
    .sort((a, b) => {
      if (b.maxMs !== a.maxMs) return b.maxMs - a.maxMs
      return b.avgMs - a.avgMs
    })
    .slice(0, 50)
}

function startAutoRefresh() {
  stopAutoRefresh()
  if (!autoRefresh.value) return
  timer = setInterval(() => {
    refreshMetrics()
  }, 3000)
}

function stopAutoRefresh() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

function toggleAutoRefresh() {
  autoRefresh.value = !autoRefresh.value
  startAutoRefresh()
}

const summary = computed(() => {
  const endpoints = rows.value.length
  const requests = rows.value.reduce((sum, row) => sum + row.count, 0)
  const slowestAvgEntry = rows.value.reduce((slowest, row) => {
    if (!slowest || row.avgMs > slowest.avgMs) return row
    return slowest
  }, null)
  const worstSingleEntry = rows.value.reduce((slowest, row) => {
    if (!slowest || row.maxMs > slowest.maxMs) return row
    return slowest
  }, null)

  return {
    endpoints,
    requests,
    slowestAvg: slowestAvgEntry ? `${slowestAvgEntry.avgMs} ms` : '-',
    worstSingle: worstSingleEntry ? `${worstSingleEntry.maxMs} ms` : '-',
  }
})

onMounted(() => {
  refreshMetrics()
  startAutoRefresh()
})

onBeforeUnmount(() => {
  stopAutoRefresh()
})
</script>

<style scoped>
.diagnostics-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 1.5rem;
}

.diag-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.diag-header h1 {
  margin: 0;
  color: #0f172a;
}

.diag-header p {
  margin: 0.4rem 0 0;
  color: #475569;
}

.diag-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn {
  border: none;
  border-radius: 8px;
  background: #0f766e;
  color: #fff;
  padding: 0.6rem 0.9rem;
  cursor: pointer;
  font-weight: 600;
}

.btn.secondary {
  background: #334155;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 0.8rem;
  margin-bottom: 1rem;
}

.summary-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.8rem;
}

.summary-card h3 {
  margin: 0;
  font-size: 0.9rem;
  color: #475569;
}

.summary-card p {
  margin: 0.5rem 0 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
}

.table-wrap {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.diag-table {
  width: 100%;
  border-collapse: collapse;
}

.diag-table th,
.diag-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}

.diag-table th {
  background: #f1f5f9;
  color: #334155;
  font-weight: 700;
}

.endpoint {
  font-family: Consolas, 'Courier New', monospace;
  font-size: 0.86rem;
}

.warn {
  color: #b91c1c;
  font-weight: 700;
}

.empty-state {
  padding: 1.5rem;
  text-align: center;
  color: #64748b;
}
</style>
