import { useId } from 'react'

type InputProps = {
  name: string
  label: string
  value: string
  onChange: (name: string, value: string) => void
  type?: 'text' | 'email' | 'password'
  placeholder?: string
  autoComplete?: string
  /** Текст ошибки под полем — сюда попадают parameter_errors с бэкенда. */
  error?: string
}

const baseClasses =
  'rounded-md border px-3 py-2 text-sm shadow-sm outline-none transition focus:ring-2'

const normalClasses = 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-200'
const errorClasses = 'border-red-400 focus:border-red-500 focus:ring-red-200'

export function Input({
  name,
  label,
  value,
  onChange,
  type = 'text',
  placeholder,
  autoComplete,
  error,
}: InputProps) {
  const id = useId()
  const errorId = `${id}-error`

  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="text-sm font-medium text-slate-700">
        {label}
      </label>

      <input
        id={id}
        name={name}
        type={type}
        value={value}
        placeholder={placeholder}
        autoComplete={autoComplete}
        aria-invalid={Boolean(error)}
        aria-describedby={error ? errorId : undefined}
        onChange={(event) => onChange(name, event.target.value)}
        className={`${baseClasses} ${error ? errorClasses : normalClasses}`}
      />

      {error && (
        <p id={errorId} className="text-sm text-red-600">
          {error}
        </p>
      )}
    </div>
  )
}
