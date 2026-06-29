import { ChevronLeft, ChevronRight } from 'lucide-react'
import type { PaginationMeta } from '../api/types'

interface PaginationProps {
  pagination: PaginationMeta
  onPageChange: (page: number) => void
}

function numerosDePage(page: number, pages: number): number[] {
  if (pages <= 7) {
    return Array.from({ length: pages }, (_, i) => i + 1)
  }

  const ensemble = new Set<number>([1, pages, page, page - 1, page + 1])
  return [...ensemble].filter((n) => n >= 1 && n <= pages).sort((a, b) => a - b)
}

export function Pagination({ pagination, onPageChange }: PaginationProps) {
  const { page, pages, total } = pagination

  if (pages <= 1) {
    return null
  }

  const numeros = numerosDePage(page, pages)

  return (
    <nav
      className="mt-8 flex flex-col items-center gap-3 border-t border-border pt-6"
      aria-label="Pagination"
    >
      <p className="text-center text-sm text-muted">
        {total} résultat{total > 1 ? 's' : ''} — page {page} sur {pages}
      </p>

      <div className="flex flex-wrap items-center justify-center gap-1">
        <button
          type="button"
          className="btn-secondary px-3 py-2 disabled:opacity-40"
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          aria-label="Page précédente"
        >
          <ChevronLeft size={18} />
        </button>

        {numeros.map((n, index) => {
          const precedent = numeros[index - 1]
          const afficherEllipsis = precedent !== undefined && n - precedent > 1

          return (
            <span key={n} className="flex items-center gap-1">
              {afficherEllipsis && <span className="px-1 text-muted">…</span>}
              <button
                type="button"
                className={`min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-medium ${
                  n === page
                    ? 'bg-brand-600 text-white'
                    : 'bg-surface-muted text-ink hover:bg-brand-50'
                }`}
                onClick={() => onPageChange(n)}
                aria-current={n === page ? 'page' : undefined}
              >
                {n}
              </button>
            </span>
          )
        })}

        <button
          type="button"
          className="btn-secondary px-3 py-2 disabled:opacity-40"
          disabled={page >= pages}
          onClick={() => onPageChange(page + 1)}
          aria-label="Page suivante"
        >
          <ChevronRight size={18} />
        </button>
      </div>
    </nav>
  )
}
