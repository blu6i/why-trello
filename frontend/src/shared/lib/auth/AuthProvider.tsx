import { useCallback, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import type { User } from '../../types'
import {
  getCurrentUser,
  login as apiLogin,
  logout as apiLogout,
  register as apiRegister,
} from '../api'
import { AuthContext } from './auth-context'
import type { AuthContextValue } from './auth-context'

/**
 * Авторизация хранится ТОЛЬКО в PHP-сессии (кука). При загрузке приложения
 * один раз спрашиваем бэкенд «кто я?» — и держим ответ в React-состоянии.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    let isCancelled = false

    getCurrentUser()
      .then((currentUser) => {
        if (!isCancelled) {
          setUser(currentUser)
        }
      })
      .finally(() => {
        if (!isCancelled) {
          setIsLoading(false)
        }
      })

    return () => {
      isCancelled = true
    }
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const loggedUser = await apiLogin(email, password)
    setUser(loggedUser)
    return loggedUser
  }, [])

  const register = useCallback(async (name: string, email: string, password: string) => {
    const newUser = await apiRegister(name, email, password)
    setUser(newUser)
    return newUser
  }, [])

  const logout = useCallback(async () => {
    await apiLogout()
    setUser(null)
  }, [])

  const value = useMemo<AuthContextValue>(
    () => ({ user, isLoading, login, register, logout }),
    [user, isLoading, login, register, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
