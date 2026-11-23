import { useState } from 'react'
import type { SubmitEvent } from 'react'
import { ApiError, formatParameterErrors, formatRequestError } from '../../shared/lib/api'
import { useAuth } from '../../shared/lib/auth'
import type { User } from '../../shared/types'

type LoginFormValues = {
  email: string
  password: string
}

const emptyValues: LoginFormValues = { email: '', password: '' }

/**
 * Состояние формы входа. Вёрстка и все компоненты уже готовы —
 * осталось подключить отправку формы к API.
 *
 * Подсказки:
 *  - useAuth() из shared/lib/auth даёт login(email, password);
 *  - shared/lib/api экспортирует ApiError, formatParameterErrors, formatRequestError;
 *  - при успехе вызовите onSuccess(user) — страница сама сделает переход.
 */
export function useLoginForm(onSuccess: (user: User) => void) {
  const { login } = useAuth()
  const [values, setValues] = useState<LoginFormValues>(emptyValues)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [formError, setFormError] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const setValue = (name: string, value: string) => {
    setValues((previous) => ({ ...previous, [name]: value }))
    setErrors((previous) => {
      const next = { ...previous }
      delete next[name]
      return next
    })
  }

  const submit = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()
    setFormError('')
    setIsSubmitting(true)

    try {
      const user = await login(values.email, values.password)
      onSuccess(user)
    } catch (error) {
      if (error instanceof ApiError) {
        const fieldErrors = formatParameterErrors(error.parameterErrors)
        setErrors(fieldErrors)
        if (Object.keys(fieldErrors).length === 0) {
          setFormError(formatRequestError(error))
        }
      } else {
        setFormError('Не удалось выполнить запрос. Попробуйте ещё раз')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return { values, errors, formError, isSubmitting, setValue, submit }
}
