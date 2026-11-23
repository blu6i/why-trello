import { Navigate, Outlet } from 'react-router'
import { useAuth } from '../shared/lib/auth'

/**
 * Защищённые маршруты: пока грузим user — «Загрузка…», гость — на /login.
 */
export function RequireAuth() {
  const { user, isLoading } = useAuth()

  if (isLoading) {
    return <p className="py-16 text-center text-slate-500">Загрузка…</p>
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  return <Outlet />
}
