import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { apiGet } from '../api/client'
import type { Produit } from '../api/types'

interface CompareContextValue {
  ids: number[]
  produits: Produit[]
  ajouter: (produit: Produit) => void
  retirer: (id: number) => void
  vider: () => void
  contient: (id: number) => boolean
  plein: boolean
}

const CompareContext = createContext<CompareContextValue | null>(null)
const STORAGE_KEY = 'novashop_compare'
const MAX_COMPARE = 4

function lireIds(): number[] {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  } catch {
    return []
  }
}

export function CompareProvider({ children }: { children: ReactNode }) {
  const [ids, setIds] = useState<number[]>(lireIds)
  const [produits, setProduits] = useState<Produit[]>([])

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids))
    if (ids.length === 0) {
      setProduits([])
      return
    }
    Promise.all(
      ids.map((id) =>
        apiGet<Produit>(`/api/products/${id}`).catch(() => null),
      ),
    ).then((resultats) => setProduits(resultats.filter((p): p is Produit => p !== null)))
  }, [ids])

  const ajouter = useCallback((produit: Produit) => {
    setIds((prev) => {
      if (prev.includes(produit.id) || prev.length >= MAX_COMPARE) return prev
      return [...prev, produit.id]
    })
  }, [])

  const retirer = useCallback((id: number) => {
    setIds((prev) => prev.filter((i) => i !== id))
  }, [])

  const vider = useCallback(() => setIds([]), [])

  const value = useMemo(
    () => ({
      ids,
      produits,
      ajouter,
      retirer,
      vider,
      contient: (id: number) => ids.includes(id),
      plein: ids.length >= MAX_COMPARE,
    }),
    [ids, produits, ajouter, retirer, vider],
  )

  return <CompareContext.Provider value={value}>{children}</CompareContext.Provider>
}

export function useCompare() {
  const ctx = useContext(CompareContext)
  if (!ctx) throw new Error('useCompare doit être utilisé dans CompareProvider')
  return ctx
}
