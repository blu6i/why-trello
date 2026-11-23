import { Link } from 'react-router'

export function NotFoundPage() {
  return (
    <div className="flex flex-col items-center gap-4 py-16 text-center">
      <h1 className="text-2xl font-semibold text-slate-900">Страница не найдена</h1>
      <p className="text-slate-600">Похоже, такой страницы в приложении нет.</p>
      <Link to="/" className="font-medium text-indigo-600 hover:text-indigo-700">
        На главную
      </Link>
    </div>
  )
}
