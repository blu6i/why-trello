import { Link } from 'react-router'
import type { User } from '../../shared/types'
import { Alert, Button, Input } from '../../shared/ui'
import { useLoginForm } from './useLoginForm'

type LoginFormProps = {
  onSuccess: (user: User) => void
}

export function LoginForm({ onSuccess }: LoginFormProps) {
  const { values, errors, formError, isSubmitting, setValue, submit } = useLoginForm(onSuccess)

  return (
    <form className="flex flex-col gap-4" onSubmit={submit} noValidate>
      <Alert message={formError} />

      <Input
        name="email"
        label="Email"
        type="email"
        value={values.email}
        onChange={setValue}
        error={errors.email}
        placeholder="ivan@example.com"
        autoComplete="email"
      />

      <Input
        name="password"
        label="Пароль"
        type="password"
        value={values.password}
        onChange={setValue}
        error={errors.password}
        placeholder="Не короче 8 символов"
        autoComplete="current-password"
      />

      <Button type="submit" isLoading={isSubmitting}>
        Войти
      </Button>

      <p className="text-center text-sm text-slate-600">
        Нет аккаунта?{' '}
        <Link to="/register" className="font-medium text-indigo-600 hover:text-indigo-700">
          Зарегистрироваться
        </Link>
      </p>
    </form>
  )
}
