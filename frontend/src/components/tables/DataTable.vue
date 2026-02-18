/**
 * Generic DataTable Component
 *
 * A reusable table component that displays data in a structured format
 * with support for custom cell rendering, sorting, and actions.
 *
 * @package Components/Tables
 */

import { computed } from 'vue'
import type { Component } from 'vue'
import { Loader2 } from 'lucide-vue-next'

/**
 * Column definition for DataTable
 */
export interface DataTableColumn<T> {
  /** Unique key for the column */
  key: string
  /** Display label for the column header */
  label: string
  /** CSS classes for the header cell */
  headerClass?: string
  /** CSS classes for the body cell */
  cellClass?: string
  /** Whether the column is sortable */
  sortable?: boolean
  /** Custom cell renderer function */
  render?: (item: T, index: number) => string | HTMLElement | Component
  /** Width of the column (e.g., 'w-32', '20%') */
  width?: string
}

/**
 * Action definition for DataTable
 */
export interface DataTableAction<T> {
  /** Unique identifier for the action */
  id: string
  /** Button label */
  label: string
  /** Icon component to display */
  icon?: Component
  /** CSS classes for the button */
  class?: string
  /** Whether the action is disabled */
  disabled?: (item: T) => boolean
  /** Whether the action should be hidden */
  hidden?: (item: T) => boolean
  /** Click handler */
  onClick: (item: T) => void
  /** Title attribute for hover tooltip */
  title?: string
}

interface Props {
  /** Column definitions */
  columns: DataTableColumn<any>[]
  /** Data items to display */
  items: any[]
  /** Unique key field for v-for */
  keyField?: string
  /** Whether data is loading */
  loading?: boolean
  /** Message to show when no data */
  emptyMessage?: string
  /** Icon component for empty state */
  emptyIcon?: Component
  /** Title for empty state */
  emptyTitle?: string
  /** Table CSS class */
  tableClass?: string
  /** Container CSS class */
  containerClass?: string
  /** Whether to show the table header */
  showHeader?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  keyField: 'id',
  loading: false,
  emptyMessage: 'No data available',
  emptyIcon: undefined,
  emptyTitle: 'No Data',
  tableClass: '',
  containerClass: '',
  showHeader: true,
})

const emit = defineEmits<{
  /** Emitted when a row is clicked */
  rowClick: [item: any, index: number]
}>()

/**
 * Check if table has any actions defined
 */
const hasActions = computed(() => {
  return false // Actions handled by parent components
})

/**
 * Get cell content for a column
 */
function getCellContent(item: any, column: DataTableColumn<any>, index: number): string | HTMLElement | Component | undefined {
  if (column.render) {
    return column.render(item, index)
  }
  return item[column.key]
}

/**
 * Handle row click
 */
function handleRowClick(item: any, index: number): void {
  emit('rowClick', item, index)
}
</script>

<template>
  <div :class="['rounded-xl border bg-card overflow-hidden', containerClass]">
    <!-- Loading State -->
    <div
      v-if="loading && !items.length"
      class="flex flex-col items-center justify-center py-12"
    >
      <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
      <p class="text-muted-foreground">Loading...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!items.length"
      class="flex flex-col items-center justify-center py-12"
    >
      <component
        :is="emptyIcon"
        v-if="emptyIcon"
        class="w-12 h-12 text-muted-foreground/50 mb-4"
      />
      <h3 class="text-lg font-medium mb-1">{{ emptyTitle }}</h3>
      <p class="text-muted-foreground text-sm">{{ emptyMessage }}</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table :class="['w-full text-sm', tableClass]">
        <thead v-if="showHeader">
          <tr class="border-b bg-muted/50">
            <th
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-4 py-3 text-left font-medium text-muted-foreground',
                column.headerClass
              ]"
              :style="column.width ? { width: column.width } : {}"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr
            v-for="(item, index) in items"
            :key="item[keyField] || index"
            class="hover:bg-muted/50 transition-colors"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="['px-4 py-3', column.cellClass]"
            >
              <component
                :is="getCellContent(item, column, index)"
                v-if="column.render && typeof getCellContent(item, column, index) !== 'string'"
                :item="item"
                :index="index"
              />
              <template v-else>
                {{ getCellContent(item, column, index) }}
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
