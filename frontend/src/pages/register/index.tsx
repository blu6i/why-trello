import { Navigate, useNavigate } from 'react-router'
import { RegisterForm } from '../../features/register'
import { useAuth } from '../../shared/lib/auth'

export function RegisterPage() {
  const { user } = useAuth()
  const navigate = useNavigate()

  // Уже авторизован — на главную
  if (user) {
    return <Navigate to="/" replace />
  }

  return (
    <div className="mx-auto flex max-w-md flex-col gap-6">
      <div>
        <h1 className="text-2xl font-semibold text-slate-900">Регистрация</h1>
        <p className="mt-1 text-slate-600">Создайте аккаунт, чтобы работать с досками.</p>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <RegisterForm onSuccess={() => navigate('/', { replace: true })} />
      </div>
    </div>
  )
}
