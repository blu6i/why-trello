type AlertProps = {
  message: string
}

export function Alert({ message }: AlertProps) {
  if (!message) {
    return null
  }

  return (
    <p
      role="alert"
      className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
    >
      {message}
    </p>
  )
}
