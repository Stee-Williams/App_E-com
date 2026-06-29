import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'

type FieldProps = {
  label: string
  hint?: string
  children: ReactNode
  className?: string
  hideLabel?: boolean
}

export function FormField({ label, hint, children, className = '', hideLabel = false }: FieldProps) {
  return (
    <label className={`block ${className}`}>
      <span className={hideLabel ? 'sr-only' : 'mb-1.5 block text-sm font-medium text-ink'}>
        {label}
      </span>
      {children}
      {hint && <span className="mt-1.5 block text-xs text-muted">{hint}</span>}
    </label>
  )
}

const inputBase =
  'input-field text-ink placeholder:text-placeholder'

export function Input({ className = '', ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return <input className={`${inputBase} ${className}`} {...props} />
}

export function Select({ className = '', children, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <select className={`select-field ${className}`} {...props}>
      {children}
    </select>
  )
}

export function Textarea({ className = '', ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return <textarea className={`${inputBase} min-h-[110px] resize-y ${className}`} {...props} />
}
