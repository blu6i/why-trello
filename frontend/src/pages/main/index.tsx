import { useAuth } from '../../shared/lib/auth'

export function MainPage() {
  const { user } = useAuth()

  return (
    <div className="flex flex-col gap-6">
      <div>
        <h1 className="text-2xl font-semibold text-slate-900">Здравствуйте, {user?.name}</h1>
        <p className="mt-1 text-slate-600">
          Это главная страница приложения. Скоро здесь появятся доски.
        </p>
      </div>

      <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
        Здесь будут ваши доски
      </div>
    </div>
  )
}
