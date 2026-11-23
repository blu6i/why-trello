import { Link } from 'react-router'
import { useAuth } from '../../shared/lib/auth'
import { Button } from '../../shared/ui'

export function Header() {
  const { user, logout } = useAuth()

  return (
    <header className="border-b border-slate-200 bg-white">
      <div className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-3">
        <Link to="/" className="flex items-center gap-2 text-lg font-semibold text-slate-900">
          <span className="grid size-8 place-items-center rounded-md bg-indigo-600 text-sm font-bold text-white">
            T
          </span>
          ТреЛоЛо
        </Link>

        {user ? (
          <div className="flex items-center gap-3">
            <span className="text-sm text-slate-600">Здравствуйте, {user.name}</span>
            <Button
              variant="secondary"
              onClick={() => {
                void logout()
              }}
            >
              Выйти
            </Button>
          </div>
        ) : (
          <Link to="/login" className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
            Войти
          </Link>
        )}
      </div>
    </header>
  )
}
