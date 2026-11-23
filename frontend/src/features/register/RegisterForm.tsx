import { Link } from 'react-router'
import type { User } from '../../shared/types'
import { Alert, Button, Input } from '../../shared/ui'
import { useRegisterForm } from './useRegisterForm'

type RegisterFormProps = {
  onSuccess: (user: User) => void
}

export function RegisterForm({ onSuccess }: RegisterFormProps) {
  const { values, errors, formError, isSubmitting, setValue, submit } = useRegisterForm(onSuccess)

  return (
    <form className="flex flex-col gap-4" onSubmit={submit} noValidate>
      <Alert message={formError} />

      <Input
        name="name"
        label="Имя"
        value={values.name}
        onChange={setValue}
        error={errors.name}
        placeholder="Иван"
        autoComplete="name"
      />

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
        autoComplete="new-password"
      />

      <Button type="submit" isLoading={isSubmitting}>
        Зарегистрироваться
      </Button>

      <p className="text-center text-sm text-slate-600">
        Уже есть аккаунт?{' '}
        <Link to="/login" className="font-medium text-indigo-600 hover:text-indigo-700">
          Войти
        </Link>
      </p>
    </form>
  )
}
