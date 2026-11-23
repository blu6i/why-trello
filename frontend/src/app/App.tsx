import { Route, Routes } from 'react-router'
import { LoginPage } from '../pages/login'
import { MainPage } from '../pages/main'
import { NotFoundPage } from '../pages/not-found'
import { RegisterPage } from '../pages/register'
import { Header } from '../widgets/header'
import { RequireAuth } from './RequireAuth'

export function App() {
  return (
    <div className="flex min-h-screen flex-col bg-slate-50">
      <Header />

      <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
        <Routes>
          <Route element={<RequireAuth />}>
            <Route index element={<MainPage />} />
          </Route>

          <Route path="login" element={<LoginPage />} />
          <Route path="register" element={<RegisterPage />} />
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </main>
      <footer className="border-t border-slate-200 bg-white py-4 text-center text-sm text-slate-500">
        © 2026 Учебный проект
      </footer>
    </div>
  )
}
