const USER_STORAGE_KEY = 'user'

export const getStoredUser = () => {
  try {
    const raw = localStorage.getItem(USER_STORAGE_KEY)
    if (!raw) {
      return null
    }
    return JSON.parse(raw)
  } catch (error) {
    console.warn('Failed to parse stored user:', error)
    return null
  }
}

export const setStoredUser = (user) => {
  localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user))
}

export const clearStoredUser = () => {
  localStorage.removeItem(USER_STORAGE_KEY)
}
