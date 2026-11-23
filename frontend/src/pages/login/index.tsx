import { Navigate, useNavigate } from 'react-router'
import { LoginForm } from '../../features/login'
import { useAuth } from '../../shared/lib/auth'

export function LoginPage() {
  const { user } = useAuth()
  const navigate = useNavigate()

  // Уже авторизован — на главную
  if (user) {
    return <Navigate to="/" replace />
  }

  return (
    <div className="mx-auto flex max-w-md flex-col gap-6">
      <div>
        <h1 className="text-2xl font-semibold text-slate-900">Вход</h1>
        <p className="mt-1 text-slate-600">Войдите, чтобы продолжить работу с досками.</p>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <LoginForm onSuccess={() => navigate('/', { replace: true })} />
      </div>
    </div>
  )
}
