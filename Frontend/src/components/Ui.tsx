import type { ReactNode } from 'react'

export function PageTitle({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <div className="mb-8">
      <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
      {subtitle && <p className="mt-2 text-muted">{subtitle}</p>}
    </div>
  )
}

export function LoadingState() {
  return (
    <div className="flex min-h-[40vh] items-center justify-center">
      <div className="h-10 w-10 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600" />
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
