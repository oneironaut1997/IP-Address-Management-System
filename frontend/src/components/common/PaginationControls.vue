<script setup lang="ts">
/**
 * Pagination Controls Component
 *
 * A reusable pagination component with page numbers, navigation buttons,
 * and page size selector.
 *
 * @package Components/Common
 */

import { computed } from 'vue'
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

/**
 * Props for the PaginationControls component
 */
interface Props {
  /** Current page number (1-indexed) */
  currentPage: number
  /** Total number of items */
  totalItems: number
  /** Items per page */
  perPage: number
  /** Available page size options */
  pageSizes?: number[]
  /** Show page size selector */
  showPageSizeSelector?: boolean
  /** Maximum number of visible page buttons */
  maxVisiblePages?: number
}

const props = withDefaults(defineProps<Props>(), {
  pageSizes: () => [10, 20, 50, 100],
  showPageSizeSelector: true,
  maxVisiblePages: 5,
})

/**
 * Events emitted by the PaginationControls component
 */
const emit = defineEmits<{
  /** Emitted when page changes */
  (e: 'page-change', page: number): void
  /** Emitted when page size changes */
  (e: 'page-size-change', pageSize: number): void
}>()

/**
 * Calculate total number of pages
 */
const totalPages = computed(() => {
  return Math.max(1, Math.ceil(props.totalItems / props.perPage))
})

/**
 * Check if there's a previous page
 */
const hasPreviousPage = computed(() => props.currentPage > 1)

/**
 * Check if there's a next page
 */
const hasNextPage = computed(() => props.currentPage < totalPages.value)

/**
 * Generate visible page numbers
 */
const visiblePages = computed(() => {
  const pages: (number | 'ellipsis')[] = []
  const total = totalPages.value
  const current = props.currentPage
  const maxVisible = props.maxVisiblePages

  if (total <= maxVisible + 2) {
    // Show all pages if total is small
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)

    // Calculate range around current page
    let start = Math.max(2, current - Math.floor(maxVisible / 2))
    const end = Math.min(total - 1, start + maxVisible - 1)

    // Adjust start if end is at max
    if (end === total - 1) {
      start = Math.max(2, end - maxVisible + 1)
    }

    // Add ellipsis before middle pages if needed
    if (start > 2) {
      pages.push('ellipsis')
    }

    // Add middle pages
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }

    // Add ellipsis after middle pages if needed
    if (end < total - 1) {
      pages.push('ellipsis')
    }

    // Always show last page
    if (total > 1) {
      pages.push(total)
    }
  }

  return pages
})

/**
 * Calculate the range of items being displayed
 */
const itemRange = computed(() => {
  const start = (props.currentPage - 1) * props.perPage + 1
  const end = Math.min(props.currentPage * props.perPage, props.totalItems)
  return { start, end }
})

/**
 * Navigate to a specific page
 */
function goToPage(page: number): void {
  if (page >= 1 && page <= totalPages.value && page !== props.currentPage) {
    emit('page-change', page)
  }
}

/**
 * Navigate to the previous page
 */
function goToPreviousPage(): void {
  if (hasPreviousPage.value) {
    goToPage(props.currentPage - 1)
  }
}

/**
 * Navigate to the next page
 */
function goToNextPage(): void {
  if (hasNextPage.value) {
    goToPage(props.currentPage + 1)
  }
}

/**
 * Navigate to the first page
 */
function goToFirstPage(): void {
  goToPage(1)
}

/**
 * Navigate to the last page
 */
function goToLastPage(): void {
  goToPage(totalPages.value)
}

/**
 * Handle page size change
 */
function handlePageSizeChange(event: Event): void {
  const target = event.target as HTMLSelectElement
  const newPageSize = parseInt(target.value, 10)
  emit('page-size-change', newPageSize)
}

/**
 * Check if page is an ellipsis
 */
function isEllipsis(page: number | 'ellipsis'): page is 'ellipsis' {
  return page === 'ellipsis'
}
</script>

<template>
  <div
    v-if="totalItems > 0"
    class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4"
  >
    <!-- Item count display -->
    <div class="text-sm text-muted-foreground">
      Showing <span class="font-medium">{{ itemRange.start }}</span> to
      <span class="font-medium">{{ itemRange.end }}</span> of
      <span class="font-medium">{{ totalItems }}</span> results
    </div>

    <!-- Pagination controls -->
    <div class="flex items-center gap-2">
      <!-- Page size selector -->
      <div v-if="showPageSizeSelector" class="flex items-center gap-2">
        <span class="text-sm text-muted-foreground">Show:</span>
        <select
          :value="perPage"
          class="rounded-lg border bg-background px-2 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          @change="handlePageSizeChange"
        >
          <option v-for="size in pageSizes" :key="size" :value="size">
            {{ size }}
          </option>
        </select>
      </div>

      <!-- Navigation buttons -->
      <div class="flex items-center gap-1">
        <!-- First page -->
        <button
          class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!hasPreviousPage"
          title="First page"
          @click="goToFirstPage"
        >
          <ChevronsLeft class="w-4 h-4" />
        </button>

        <!-- Previous page -->
        <button
          class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!hasPreviousPage"
          title="Previous page"
          @click="goToPreviousPage"
        >
          <ChevronLeft class="w-4 h-4" />
        </button>
        
        <!-- Page numbers -->
        <span
        v-for="(page, index) in visiblePages"
        :key="isEllipsis(page) ? `ellipsis-${index}` : `page-${page}`"
        >
          <span
            v-if="isEllipsis(page)"
            class="px-2 text-muted-foreground"
          >...</span>
          <button
            v-else
            :class="cn(
              'inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-medium transition-colors',
              page == currentPage
                ? 'bg-primary text-primary-foreground'
                : 'hover:bg-muted'
            )"
            :title="`Page ${page}`"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
        </span>

        <!-- Next page -->
        <button
          class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!hasNextPage"
          title="Next page"
          @click="goToNextPage"
        >
          <ChevronRight class="w-4 h-4" />
        </button>

        <!-- Last page -->
        <button
          class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!hasNextPage"
          title="Last page"
          @click="goToLastPage"
        >
          <ChevronsRight class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>
