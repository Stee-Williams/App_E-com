import type { ReactNode } from 'react'

export function PageTitle({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <div className="mb-8 border-b border-border pb-5">
      <p className="mb-2 text-[10px] font-bold uppercase tracking-[0.22em] text-muted">NovaShop</p>
      <h1 className="font-display text-4xl font-black uppercase leading-none tracking-[-0.02em] sm:text-5xl">{title}</h1>
      {subtitle && <p className="mt-3 max-w-[65ch] text-sm leading-relaxed text-muted">{subtitle}</p>}
    </div>
  )
}

export function LoadingState() {
  return (
    <div role="status" aria-label="Chargement" aria-busy="true" className="min-h-[40vh] animate-pulse">
      <span className="sr-only">Chargement de la page…</span>
      <div className="mb-8 border-b border-border pb-5">
        <div className="h-3 w-24 rounded bg-surface-muted" />
        <div className="mt-4 h-10 w-2/5 rounded-lg bg-surface-muted" />
        <div className="mt-3 h-4 w-3/5 rounded bg-surface-muted" />
      </div>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {[0, 1, 2].map((item) => (
          <div key={item} className="overflow-hidden rounded-xl border border-border">
            <div className="aspect-[4/3] bg-surface-muted" />
            <div className="space-y-3 p-4">
              <div className="h-3 w-1/3 rounded bg-surface-muted" />
              <div className="h-5 w-3/4 rounded bg-surface-muted" />
              <div className="h-4 w-1/2 rounded bg-surface-muted" />
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export function EmptyState({ message, action }: { message: string; action?: ReactNode }) {
  return (
    <div className="rounded-2xl border border-dashed border-border bg-surface p-12 text-center">
      <p className="text-muted">{message}</p>
      {action && <div className="mt-4">{action}</div>}
    </div>
  )
}

export function ErrorState({ message }: { message: string }) {
  return (
    <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
      {message}
    </div>
  )
}
