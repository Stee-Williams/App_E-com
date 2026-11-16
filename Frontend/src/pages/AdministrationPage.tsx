import { useEffect, useRef, useState } from 'react'
import { Navigate, useSearchParams } from 'react-router-dom'
import { ArrowRight, BarChart3, ClipboardList, Package, Pencil, Tags, TicketPercent, Trash2, Users } from 'lucide-react'
import { api, apiGetCached } from '../api/client'
import { TTL, invaliderCache, lireCache } from '../api/cache'
import type { AdminStats, BonReduction, Categorie, Commande, PaginatedResult, PaginationMeta, Produit, Utilisateur } from '../api/types'
import { STATUTS_COMMANDE, formatPrix } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { invaliderCacheProduits, useCategories } from '../contexts/CategoriesContext'
import { FormField, Input, Select, Textarea } from '../components/Form'
import { Pagination } from '../components/Pagination'
import { EmptyState, ErrorState, LoadingState, PageTitle } from '../components/Ui'

const LIMIT_ADMIN = 10
const PAGINATION_VIDE: PaginationMeta = { page: 1, limit: LIMIT_ADMIN, total: 0, pages: 1 }
const FORM_ADMIN = 'admin-form'
const FORM_ADMIN_LARGE = 'admin-form admin-form-lg'
type OngletAdmin = 'stats' | 'commandes' | 'produits' | 'categories' | 'utilisateurs' | 'bons'
type TriCommandes = 'recent' | 'ancien' | 'montant_asc' | 'montant_desc'
type TriProduits = 'recent' | 'nom_asc' | 'nom_desc' | 'prix_asc' | 'prix_desc' | 'stock_asc' | 'stock_desc'
type StatutCommandeFiltre = '' | 'en_attente' | 'confirmee' | 'expediee' | 'livree' | 'annulee'
const ONGLET_ADMIN: OngletAdmin[] = ['stats', 'commandes', 'produits', 'categories', 'utilisateurs', 'bons']

function estTriCommandes(valeur: string): valeur is TriCommandes {
  return ['recent', 'ancien', 'montant_asc', 'montant_desc'].includes(valeur)
}

function estTriProduits(valeur: string): valeur is TriProduits {
  return ['recent', 'nom_asc', 'nom_desc', 'prix_asc', 'prix_desc', 'stock_asc', 'stock_desc'].includes(valeur)
}

function estStatutCommandeFiltre(valeur: string): valeur is Exclude<StatutCommandeFiltre, ''> {
  return ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'].includes(valeur)
}

function lireOngletAdmin(valeur: string | null): OngletAdmin {
  return ONGLET_ADMIN.find((onglet) => onglet === valeur) ?? 'stats'
}

function SousOnglets<T extends string>({
  onglets,
  actif,
  onChange,
}: {
  onglets: { id: T; label: string }[]
  actif: T
  onChange: (id: T) => void
}) {
  return (
    <div className="mb-5 inline-flex max-w-full flex-wrap gap-1 rounded-xl border border-border bg-surface-muted p-1" role="tablist">
      {onglets.map((o) => (
        <button
          key={o.id}
          type="button"
          onClick={() => onChange(o.id)}
          role="tab"
          aria-selected={actif === o.id}
          className={`rounded-lg px-3 py-2 text-sm font-semibold transition-colors ${
            actif === o.id ? 'bg-brand-300 text-brand-600 shadow-sm' : 'text-muted hover:bg-surface hover:text-heading'
          }`}
        >
          {o.label}
        </button>
      ))}
    </div>
  )
}

function formatMois(mois: string) {
  const [annee, numeroMois] = mois.split('-').map(Number)
  if (!Number.isInteger(annee) || !Number.isInteger(numeroMois) || numeroMois < 1 || numeroMois > 12) {
    return mois === 'inconnu' ? '—' : mois
  }

  return new Date(annee, numeroMois - 1, 1)
    .toLocaleDateString('fr-FR', { month: 'short' })
    .replace('.', '')
}

function GraphiqueCommandes({ donnees }: { donnees: AdminStats['commandesParMois'] }) {
  const indexParMois = new Map(donnees.map(({ mois, total }) => [mois, total]))
  const aujourdHui = new Date()
  const recentes = Array.from({ length: 6 }, (_, index) => {
    const date = new Date(aujourdHui.getFullYear(), aujourdHui.getMonth() - 5 + index, 1)
    const mois = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    return { mois, total: indexParMois.get(mois) ?? 0 }
  })
  const maximum = Math.max(1, ...recentes.map(({ total }) => total))

  const description = recentes.map(({ mois, total }) => `${formatMois(mois)} : ${total}`).join(', ')

  return (
    <div
      role="img"
      aria-label={`Commandes des six derniers mois : ${description}`}
      className="grid h-56 grid-cols-3 items-end gap-4 pt-5 sm:grid-cols-6"
    >
      {recentes.map(({ mois, total }) => (
        <div key={mois} className="flex h-full min-w-0 flex-col items-center justify-end gap-2">
          <span className="text-xs font-semibold tabular-nums text-ink">{total}</span>
          <div className="flex min-h-0 w-full flex-1 items-end justify-center">
            <div
              className="w-full max-w-12 rounded-t-lg bg-brand-300 transition-[height] duration-200"
              style={{ height: `${total === 0 ? 4 : Math.max(12, (total / maximum) * 100)}%` }}
            />
          </div>
          <span className="text-[11px] font-medium capitalize text-muted">{formatMois(mois)}</span>
        </div>
      ))}
    </div>
  )
}

const COULEURS_STATUT: Record<string, string> = {
  en_attente: 'bg-amber-400',
  confirmee: 'bg-sky-500',
  expediee: 'bg-violet-500',
  livree: 'bg-emerald-500',
  annulee: 'bg-rose-500',
}

function GraphiqueStatuts({ statuts }: { statuts: AdminStats['statutsCommandes'] }) {
  const codesStatut = new Set([...Object.keys(STATUTS_COMMANDE), ...Object.keys(statuts)])
  const donnees = [...codesStatut]
    .map((code) => ({ code, total: statuts[code] ?? 0 }))
    .sort((a, b) => b.total - a.total)
  const totalCommandes = donnees.reduce((total, ligne) => total + ligne.total, 0)

  return (
    <div className="space-y-5">
      {donnees.map(({ code, total }) => {
        const pourcentage = totalCommandes > 0 ? Math.round((total / totalCommandes) * 100) : 0
        const label = STATUTS_COMMANDE[code] ?? code

        return (
          <div key={code}>
            <div className="mb-2 flex items-center justify-between gap-3 text-sm">
              <span className="font-medium text-ink">{label}</span>
              <span className="shrink-0 tabular-nums text-muted">{total} · {pourcentage}%</span>
            </div>
            <div
              className="h-2 overflow-hidden rounded-full bg-surface-muted"
              role="progressbar"
              aria-label={`${label} : ${pourcentage}%`}
              aria-valuenow={pourcentage}
              aria-valuemin={0}
              aria-valuemax={100}
            >
              <div className={`h-full rounded-full ${COULEURS_STATUT[code] ?? 'bg-brand-300'}`} style={{ width: `${pourcentage}%` }} />
            </div>
          </div>
        )
      })}
    </div>
  )
}

function FormCreerCategorie({ onCree }: { onCree: (c: Categorie) => void }) {
  const [nom, setNom] = useState('')
  const [description, setDescription] = useState('')
  const [chargement, setChargement] = useState(false)
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    setChargement(true)
    setErreur('')
    setMessage('')
    try {
      const categorie = await api<Categorie>('/api/categories', {
        method: 'POST',
        body: JSON.stringify({ nom, description: description || null }),
      })
      onCree(categorie)
      setNom('')
      setDescription('')
      setMessage(`Catégorie « ${categorie.nom} » créée.`)
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    } finally {
      setChargement(false)
    }
  }

  return (
    <form onSubmit={soumettre} className={FORM_ADMIN}>
      <FormField label="Nom">
        <Input placeholder="Ex. Électronique" value={nom} onChange={(e) => setNom(e.target.value)} required />
      </FormField>
      <FormField label="Description">
        <Textarea rows={3} placeholder="Description courte…" value={description} onChange={(e) => setDescription(e.target.value)} />
      </FormField>
      {erreur && <p className="text-sm text-red-600">{erreur}</p>}
      {message && <p className="text-sm text-green-600">{message}</p>}
      <button type="submit" className="btn-primary" disabled={chargement}>
        {chargement ? 'Création…' : 'Créer la catégorie'}
      </button>
    </form>
  )
}

function FormModifierCategorie({
  categorie,
  onAnnuler,
  onModifie,
}: {
  categorie: Categorie
  onAnnuler: () => void
  onModifie: (c: Categorie) => void
}) {
  const [nom, setNom] = useState(categorie.nom)
  const [description, setDescription] = useState(categorie.description ?? '')
  const [chargement, setChargement] = useState(false)
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    setNom(categorie.nom)
    setDescription(categorie.description ?? '')
    setMessage('')
    setErreur('')
  }, [categorie])

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    setChargement(true)
    setErreur('')
    setMessage('')
    try {
      const modifiee = await api<Categorie>(`/api/categories/${categorie.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ nom, description: description || null }),
      })
      onModifie(modifiee)
      setMessage(`Catégorie « ${modifiee.nom} » mise à jour.`)
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    } finally {
      setChargement(false)
    }
  }

  return (
    <form onSubmit={soumettre} className={`${FORM_ADMIN} mb-4`}>
      <p className="text-sm font-semibold text-ink">Modifier la catégorie</p>
      <FormField label="Nom">
        <Input value={nom} onChange={(e) => setNom(e.target.value)} required />
      </FormField>
      <FormField label="Description">
        <Textarea rows={3} value={description} onChange={(e) => setDescription(e.target.value)} />
      </FormField>
      {erreur && <p className="text-sm text-red-600">{erreur}</p>}
      {message && <p className="text-sm text-green-600">{message}</p>}
      <div className="flex flex-wrap gap-2">
        <button type="submit" className="btn-primary" disabled={chargement}>
          {chargement ? 'Enregistrement…' : 'Enregistrer'}
        </button>
        <button type="button" className="btn-secondary" onClick={onAnnuler} disabled={chargement}>
          Annuler
        </button>
      </div>
    </form>
  )
}

function FormCreerProduit({
  categories,
  onCree,
}: {
  categories: Categorie[]
  onCree: (p: Produit) => void
}) {
  const [form, setForm] = useState({
    nom: '',
    description: '',
    prix: '',
    prixPromo: '',
    stock: '0',
    categorieId: '',
  })
  const [image, setImage] = useState<File | null>(null)
  const [chargement, setChargement] = useState(false)
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.categorieId) {
      setErreur('Choisissez une catégorie.')
      return
    }
    setChargement(true)
    setErreur('')
    setMessage('')
    try {
      const produit = await api<Produit>('/api/products', {
        method: 'POST',
        body: JSON.stringify({
          nom: form.nom,
          description: form.description || null,
          prix: Number(form.prix),
          prixPromo: form.prixPromo ? Number(form.prixPromo) : null,
          stock: Number(form.stock),
          categorieId: Number(form.categorieId),
          actif: true,
        }),
      })

      if (image) {
        const fd = new FormData()
        fd.append('image', image)
        const avecImage = await api<Produit>(`/api/products/${produit.id}/image`, {
          method: 'POST',
          body: fd,
        })
        onCree(avecImage)
      } else {
        onCree(produit)
      }

      setForm({ nom: '', description: '', prix: '', prixPromo: '', stock: '0', categorieId: form.categorieId })
      setImage(null)
      setMessage(`Produit « ${produit.nom} » créé.`)
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    } finally {
      setChargement(false)
    }
  }

  return (
    <form onSubmit={soumettre} className={FORM_ADMIN_LARGE}>
      <div className="grid gap-3 sm:grid-cols-2">
        <FormField label="Nom">
          <Input placeholder="Nom du produit" value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required />
        </FormField>
        <FormField label="Catégorie">
          <Select
            value={form.categorieId}
            onChange={(e) => setForm({ ...form, categorieId: e.target.value })}
            required
          >
            <option value="">Sélectionner…</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>{c.nom}</option>
            ))}
          </Select>
        </FormField>
      </div>
      <FormField label="Description">
        <Textarea rows={3} placeholder="Description du produit…" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
      </FormField>
      <div className="grid gap-3 sm:grid-cols-3">
        <FormField label="Prix (FCFA)">
          <Input type="number" min={0} placeholder="25000" value={form.prix} onChange={(e) => setForm({ ...form, prix: e.target.value })} required />
        </FormField>
        <FormField label="Prix promo (FCFA)">
          <Input type="number" min={0} placeholder="Optionnel" value={form.prixPromo} onChange={(e) => setForm({ ...form, prixPromo: e.target.value })} />
        </FormField>
        <FormField label="Stock">
          <Input type="number" min={0} value={form.stock} onChange={(e) => setForm({ ...form, stock: e.target.value })} required />
        </FormField>
      </div>
      <FormField label="Image" hint="JPG, PNG ou WebP — max. quelques Mo">
        <Input
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          className="file:mr-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-brand-700"
          onChange={(e) => setImage(e.target.files?.[0] ?? null)}
        />
      </FormField>
      {erreur && <p className="text-sm text-red-600">{erreur}</p>}
      {message && <p className="text-sm text-green-600">{message}</p>}
      <button type="submit" className="btn-primary" disabled={chargement || categories.length === 0}>
        {chargement ? 'Création…' : 'Créer le produit'}
      </button>
      {categories.length === 0 && (
        <p className="text-sm text-muted">Créez d&apos;abord une catégorie dans l&apos;onglet Catégories.</p>
      )}
    </form>
  )
}

function FormModifierProduit({
  produit,
  categories,
  onAnnuler,
  onModifie,
}: {
  produit: Produit
  categories: Categorie[]
  onAnnuler: () => void
  onModifie: () => void
}) {
  const [form, setForm] = useState({
    nom: produit.nom,
    description: produit.description ?? '',
    prix: produit.prix,
    prixPromo: produit.prixPromo ?? '',
    stock: String(produit.stock),
    categorieId: String(produit.categorie?.id ?? ''),
    actif: produit.actif !== false,
  })
  const [image, setImage] = useState<File | null>(null)
  const [chargement, setChargement] = useState(false)
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    setForm({
      nom: produit.nom,
      description: produit.description ?? '',
      prix: produit.prix,
      prixPromo: produit.prixPromo ?? '',
      stock: String(produit.stock),
      categorieId: String(produit.categorie?.id ?? ''),
      actif: produit.actif !== false,
    })
    setImage(null)
    setMessage('')
    setErreur('')
  }, [produit])

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.categorieId) {
      setErreur('Choisissez une catégorie.')
      return
    }
    setChargement(true)
    setErreur('')
    setMessage('')
    try {
      await api<Produit>(`/api/products/${produit.id}`, {
        method: 'PATCH',
        body: JSON.stringify({
          nom: form.nom,
          description: form.description || null,
          prix: Number(form.prix),
          prixPromo: form.prixPromo ? Number(form.prixPromo) : null,
          stock: Number(form.stock),
          categorieId: Number(form.categorieId),
          actif: form.actif,
        }),
      })

      if (image) {
        const fd = new FormData()
        fd.append('image', image)
        await api<Produit>(`/api/products/${produit.id}/image`, {
          method: 'POST',
          body: fd,
        })
      }

      setMessage(`Produit « ${form.nom} » mis à jour.`)
      onModifie()
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    } finally {
      setChargement(false)
    }
  }

  return (
    <form onSubmit={soumettre} className={`${FORM_ADMIN_LARGE} mb-6`}>
      <p className="mb-3 text-sm font-semibold text-ink">
        Modifier : {produit.nom}
      </p>
      <div className="grid gap-3 sm:grid-cols-2">
        <FormField label="Nom">
          <Input value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required />
        </FormField>
        <FormField label="Catégorie">
          <Select
            value={form.categorieId}
            onChange={(e) => setForm({ ...form, categorieId: e.target.value })}
            required
          >
            <option value="">Sélectionner…</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>{c.nom}</option>
            ))}
          </Select>
        </FormField>
      </div>
      <FormField label="Description">
        <Textarea rows={3} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
      </FormField>
      <div className="grid gap-3 sm:grid-cols-3">
        <FormField label="Prix (FCFA)">
          <Input type="number" min={0} value={form.prix} onChange={(e) => setForm({ ...form, prix: e.target.value })} required />
        </FormField>
        <FormField label="Prix promo (FCFA)">
          <Input
            type="number"
            min={0}
            placeholder="Optionnel"
            value={form.prixPromo}
            onChange={(e) => setForm({ ...form, prixPromo: e.target.value })}
          />
        </FormField>
        <FormField label="Stock">
          <Input type="number" min={0} value={form.stock} onChange={(e) => setForm({ ...form, stock: e.target.value })} required />
        </FormField>
      </div>
      <FormField label="Nouvelle image" hint="Laisser vide pour conserver l'image actuelle">
        <Input
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          className="file:mr-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-brand-700"
          onChange={(e) => setImage(e.target.files?.[0] ?? null)}
        />
      </FormField>
      <label className="flex items-center gap-2 text-sm text-ink">
        <input
          type="checkbox"
          className="rounded border-border text-brand-600 focus:ring-brand-500"
          checked={form.actif}
          onChange={(e) => setForm({ ...form, actif: e.target.checked })}
        />
        Produit visible dans le catalogue
      </label>
      {erreur && <p className="text-sm text-red-600">{erreur}</p>}
      {message && <p className="text-sm text-green-600">{message}</p>}
      <div className="flex flex-wrap gap-2">
        <button type="submit" className="btn-primary" disabled={chargement}>
          {chargement ? 'Enregistrement…' : 'Enregistrer les modifications'}
        </button>
        <button type="button" className="btn-secondary" onClick={onAnnuler} disabled={chargement}>
          Annuler
        </button>
      </div>
    </form>
  )
}

function FormCreerCoupon({ onCree }: { onCree: (b: BonReduction) => void }) {
  const [form, setForm] = useState({
    code: '',
    type: 'pourcentage' as 'pourcentage' | 'montant_fixe',
    valeur: '',
    montantMinimum: '',
    utilisationsMax: '',
    dateDebut: '',
    dateFin: '',
    actif: true,
  })
  const [chargement, setChargement] = useState(false)
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    setChargement(true)
    setErreur('')
    setMessage('')
    try {
      const bon = await api<BonReduction>('/api/coupons', {
        method: 'POST',
        body: JSON.stringify({
          code: form.code.trim().toUpperCase(),
          type: form.type,
          valeur: Number(form.valeur),
          montantMinimum: form.montantMinimum ? Number(form.montantMinimum) : null,
          utilisationsMax: form.utilisationsMax ? Number(form.utilisationsMax) : null,
          dateDebut: form.dateDebut || null,
          dateFin: form.dateFin || null,
          actif: form.actif,
        }),
      })
      onCree(bon)
      setForm({
        code: '',
        type: 'pourcentage',
        valeur: '',
        montantMinimum: '',
        utilisationsMax: '',
        dateDebut: '',
        dateFin: '',
        actif: true,
      })
      setMessage(`Coupon « ${bon.code} » créé.`)
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    } finally {
      setChargement(false)
    }
  }

  return (
    <form onSubmit={soumettre} className={FORM_ADMIN_LARGE}>
      <div className="grid gap-3 sm:grid-cols-2">
        <FormField label="Code promo">
          <Input
            placeholder="Ex. ETE2026"
            value={form.code}
            onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })}
            required
          />
        </FormField>
        <FormField label="Type de réduction">
          <Select
            value={form.type}
            onChange={(e) => setForm({ ...form, type: e.target.value as 'pourcentage' | 'montant_fixe' })}
          >
            <option value="pourcentage">Pourcentage (%)</option>
            <option value="montant_fixe">Montant fixe (FCFA)</option>
          </Select>
        </FormField>
      </div>
      <div className="grid gap-3 sm:grid-cols-3">
        <FormField label={form.type === 'pourcentage' ? 'Réduction (%)' : 'Montant (FCFA)'}>
          <Input
            type="number"
            min={0}
            max={form.type === 'pourcentage' ? 100 : undefined}
            placeholder={form.type === 'pourcentage' ? '10' : '2500'}
            value={form.valeur}
            onChange={(e) => setForm({ ...form, valeur: e.target.value })}
            required
          />
        </FormField>
        <FormField label="Panier minimum (FCFA)">
          <Input
            type="number"
            min={0}
            placeholder="Optionnel"
            value={form.montantMinimum}
            onChange={(e) => setForm({ ...form, montantMinimum: e.target.value })}
          />
        </FormField>
        <FormField label="Utilisations max">
          <Input
            type="number"
            min={1}
            placeholder="Illimité"
            value={form.utilisationsMax}
            onChange={(e) => setForm({ ...form, utilisationsMax: e.target.value })}
          />
        </FormField>
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        <FormField label="Date de début">
          <Input type="date" value={form.dateDebut} onChange={(e) => setForm({ ...form, dateDebut: e.target.value })} />
        </FormField>
        <FormField label="Date de fin">
          <Input type="date" value={form.dateFin} onChange={(e) => setForm({ ...form, dateFin: e.target.value })} />
        </FormField>
      </div>
      <label className="flex items-center gap-2 text-sm text-ink">
        <input
          type="checkbox"
          className="rounded border-border text-brand-600 focus:ring-brand-500"
          checked={form.actif}
          onChange={(e) => setForm({ ...form, actif: e.target.checked })}
        />
        Coupon actif dès la création
      </label>
      {erreur && <p className="text-sm text-red-600">{erreur}</p>}
      {message && <p className="text-sm text-green-600">{message}</p>}
      <button type="submit" className="btn-primary" disabled={chargement}>
        {chargement ? 'Création…' : 'Créer le coupon'}
      </button>
    </form>
  )
}

function FormModifierCoupon({
  bon,
  onAnnuler,
  onModifie,
}: {
  bon: BonReduction
  onAnnuler: () => void
  onModifie: (b: BonReduction) => void
}) {
  const [form, setForm] = useState({
    code: bon.code,
    type: bon.type,
    valeur: String(bon.valeur),
    actif: bon.actif !== false,
  })
  const [chargement, setChargement] = useState(false)
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    setForm({
      code: bon.code,
      type: bon.type,
      valeur: String(bon.valeur),
      actif: bon.actif !== false,
    })
    setMessage('')
    setErreur('')
  }, [bon])

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    setChargement(true)
    setErreur('')
    setMessage('')
    try {
      const modifie = await api<BonReduction>(`/api/coupons/${bon.id}`, {
        method: 'PATCH',
        body: JSON.stringify({
          code: form.code.trim().toUpperCase(),
          type: form.type,
          valeur: Number(form.valeur),
          actif: form.actif,
        }),
      })
      onModifie(modifie)
      setMessage(`Coupon « ${modifie.code} » mis à jour.`)
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    } finally {
      setChargement(false)
    }
  }

  return (
    <form onSubmit={soumettre} className={`${FORM_ADMIN_LARGE} mb-4`}>
      <p className="text-sm font-semibold text-ink">Modifier le coupon</p>
      <div className="grid gap-3 sm:grid-cols-3">
        <FormField label="Code">
          <Input
            value={form.code}
            onChange={(e) => setForm((prev) => ({ ...prev, code: e.target.value.toUpperCase() }))}
            required
          />
        </FormField>
        <FormField label="Type">
          <Select
            value={form.type}
            onChange={(e) => setForm((prev) => ({ ...prev, type: e.target.value as 'pourcentage' | 'montant_fixe' }))}
          >
            <option value="pourcentage">Pourcentage (%)</option>
            <option value="montant_fixe">Montant fixe (FCFA)</option>
          </Select>
        </FormField>
        <FormField label={form.type === 'pourcentage' ? 'Réduction (%)' : 'Montant (FCFA)'}>
          <Input
            type="number"
            min={0}
            max={form.type === 'pourcentage' ? 100 : undefined}
            value={form.valeur}
            onChange={(e) => setForm((prev) => ({ ...prev, valeur: e.target.value }))}
            required
          />
        </FormField>
      </div>
      <label className="flex items-center gap-2 text-sm text-ink">
        <input
          type="checkbox"
          className="rounded border-border text-brand-600 focus:ring-brand-500"
          checked={form.actif}
          onChange={(e) => setForm((prev) => ({ ...prev, actif: e.target.checked }))}
        />
        Coupon actif
      </label>
      {erreur && <p className="text-sm text-red-600">{erreur}</p>}
      {message && <p className="text-sm text-green-600">{message}</p>}
      <div className="flex flex-wrap gap-2">
        <button type="submit" className="btn-primary" disabled={chargement}>
          {chargement ? 'Enregistrement…' : 'Enregistrer'}
        </button>
        <button type="button" className="btn-secondary" onClick={onAnnuler} disabled={chargement}>
          Annuler
        </button>
      </div>
    </form>
  )
}

export default function AdministrationPage() {
  const { estAdmin, chargement: authChargement } = useAuth()
  const { ajouterCategorie: ajouterCategoriePublique } = useCategories()
  const [searchParams, setSearchParams] = useSearchParams()
  const ongletDansUrl = searchParams.get('onglet')
  const [stats, setStats] = useState<AdminStats | null>(null)
  const [commandes, setCommandes] = useState<Commande[]>([])
  const [commandeSelectionnee, setCommandeSelectionnee] = useState<Commande | null>(null)
  const dialogueCommandeRef = useRef<HTMLDialogElement>(null)
  const [produits, setProduits] = useState<Produit[]>([])
  const [categories, setCategories] = useState<Categorie[]>([])
  const [utilisateurs, setUtilisateurs] = useState<Utilisateur[]>([])
  const [bons, setBons] = useState<BonReduction[]>([])
  const [paginationCommandes, setPaginationCommandes] = useState<PaginationMeta>(PAGINATION_VIDE)
  const [paginationProduits, setPaginationProduits] = useState<PaginationMeta>(PAGINATION_VIDE)
  const [paginationUtilisateurs, setPaginationUtilisateurs] = useState<PaginationMeta>(PAGINATION_VIDE)
  const [paginationBons, setPaginationBons] = useState<PaginationMeta>(PAGINATION_VIDE)
  const [pageCommandes, setPageCommandes] = useState(1)
  const [pageProduits, setPageProduits] = useState(1)
  const [triCommandes, setTriCommandes] = useState<TriCommandes>('recent')
  const [triProduits, setTriProduits] = useState<TriProduits>('recent')
  const [statutCommandeFiltre, setStatutCommandeFiltre] = useState<StatutCommandeFiltre>('')
  const [pageUtilisateurs, setPageUtilisateurs] = useState(1)
  const [pageBons, setPageBons] = useState(1)
  const [rafraichirProduits, setRafraichirProduits] = useState(0)
  const [rafraichirBons, setRafraichirBons] = useState(0)
  const [chargementInitial, setChargementInitial] = useState(true)
  const [chargementOnglet, setChargementOnglet] = useState(false)
  const [erreur, setErreur] = useState('')
  const [onglet, setOnglet] = useState<OngletAdmin>(() => lireOngletAdmin(ongletDansUrl))
  const [sousOngletProduits, setSousOngletProduits] = useState<'liste' | 'nouveau'>('liste')
  const [sousOngletBons, setSousOngletBons] = useState<'liste' | 'nouveau'>('liste')
  const [sousOngletCategories, setSousOngletCategories] = useState<'liste' | 'nouveau'>('liste')
  const [produitEnEdition, setProduitEnEdition] = useState<Produit | null>(null)
  const [categorieEnEdition, setCategorieEnEdition] = useState<Categorie | null>(null)
  const [couponEnEdition, setCouponEnEdition] = useState<BonReduction | null>(null)

  useEffect(() => {
    const dialogue = dialogueCommandeRef.current
    if (!commandeSelectionnee || !dialogue) return
    dialogue.showModal()
    return () => {
      if (dialogue.open) dialogue.close()
    }
  }, [commandeSelectionnee])

  useEffect(() => {
    setOnglet(lireOngletAdmin(ongletDansUrl))
  }, [ongletDansUrl])

  useEffect(() => {
    if (!estAdmin) return
    const statsCache = lireCache<AdminStats>('GET:/api/admin/stats')
    const catCache = lireCache<Categorie[]>('GET:/api/admin/categories')
    if (statsCache && catCache) {
      setStats(statsCache)
      setCategories(catCache)
      setChargementInitial(false)
      return
    }
    setChargementInitial(true)
    setErreur('')
    Promise.all([
      apiGetCached<AdminStats>('/api/admin/stats', TTL.admin),
      apiGetCached<Categorie[]>('/api/admin/categories', TTL.admin),
    ])
      .then(([s, cat]) => {
        setStats(s)
        setCategories(cat)
      })
      .catch((e) => setErreur(e.message))
      .finally(() => setChargementInitial(false))
  }, [estAdmin])

  useEffect(() => {
    if (!estAdmin) return

    const chargerOnglet = async () => {
      setErreur('')
      try {
        if (onglet === 'commandes') {
          const chemin = `/api/orders?page=${pageCommandes}&limit=${LIMIT_ADMIN}&tri=${triCommandes}&statut=${statutCommandeFiltre}`
          const enCache = lireCache<PaginatedResult<Commande>>(`GET:${chemin}`)
          if (enCache) {
            setCommandes(enCache.items)
            setPaginationCommandes(enCache.pagination)
            return
          }
          setChargementOnglet(true)
          const resultat = await apiGetCached<PaginatedResult<Commande>>(chemin, TTL.admin)
          setCommandes(resultat.items)
          setPaginationCommandes(resultat.pagination)
        } else if (onglet === 'produits') {
          const chemin = `/api/admin/products?page=${pageProduits}&limit=${LIMIT_ADMIN}&tri=${triProduits}`
          const enCache = lireCache<PaginatedResult<Produit>>(`GET:${chemin}`)
          if (enCache) {
            setProduits(enCache.items)
            setPaginationProduits(enCache.pagination)
            return
          }
          setChargementOnglet(true)
          const resultat = await apiGetCached<PaginatedResult<Produit>>(chemin, TTL.admin)
          setProduits(resultat.items)
          setPaginationProduits(resultat.pagination)
        } else if (onglet === 'utilisateurs') {
          const chemin = `/api/users?page=${pageUtilisateurs}&limit=${LIMIT_ADMIN}`
          const enCache = lireCache<PaginatedResult<Utilisateur>>(`GET:${chemin}`)
          if (enCache) {
            setUtilisateurs(enCache.items)
            setPaginationUtilisateurs(enCache.pagination)
            return
          }
          setChargementOnglet(true)
          const resultat = await apiGetCached<PaginatedResult<Utilisateur>>(chemin, TTL.admin)
          setUtilisateurs(resultat.items)
          setPaginationUtilisateurs(resultat.pagination)
        } else if (onglet === 'bons') {
          const chemin = `/api/coupons?page=${pageBons}&limit=${LIMIT_ADMIN}`
          const enCache = lireCache<PaginatedResult<BonReduction>>(`GET:${chemin}`)
          if (enCache) {
            setBons(enCache.items)
            setPaginationBons(enCache.pagination)
            return
          }
          setChargementOnglet(true)
          const resultat = await apiGetCached<PaginatedResult<BonReduction>>(chemin, TTL.admin)
          setBons(resultat.items)
          setPaginationBons(resultat.pagination)
        }
      } catch (e) {
        setErreur(e instanceof Error ? e.message : 'Erreur')
      } finally {
        setChargementOnglet(false)
      }
    }

    if (['commandes', 'produits', 'utilisateurs', 'bons'].includes(onglet)) {
      chargerOnglet()
    }
  }, [estAdmin, onglet, pageCommandes, pageProduits, triCommandes, triProduits, statutCommandeFiltre, pageUtilisateurs, pageBons, rafraichirProduits, rafraichirBons])

  useEffect(() => {
    setProduitEnEdition(null)
  }, [pageProduits])

  const apresCreationProduit = () => {
    setSousOngletProduits('liste')
    setPageProduits(1)
    invaliderCacheProduits()
    invaliderCache('GET:/api/admin/products')
    setRafraichirProduits((n) => n + 1)
  }

  const apresModificationProduit = () => {
    setProduitEnEdition(null)
    invaliderCacheProduits()
    invaliderCache('GET:/api/admin/products')
    setRafraichirProduits((n) => n + 1)
  }

  const apresCreationCategorie = (categorie: Categorie) => {
    setCategories((prev) => [...prev, categorie].sort((a, b) => a.nom.localeCompare(b.nom)))
    ajouterCategoriePublique(categorie)
    invaliderCache('GET:/api/admin/categories')
    invaliderCache('GET:/api/categories')
    setSousOngletCategories('liste')
  }

  const apresModificationCategorie = (categorie: Categorie) => {
    setCategories((prev) => prev.map((c) => (c.id === categorie.id ? categorie : c)).sort((a, b) => a.nom.localeCompare(b.nom)))
    setCategorieEnEdition(null)
    invaliderCache('GET:/api/admin/categories')
    invaliderCache('GET:/api/categories')
  }

  const supprimerCategorie = async (categorie: Categorie) => {
    if (!window.confirm(`Supprimer la catégorie « ${categorie.nom} » ?`)) return
    try {
      await api(`/api/categories/${categorie.id}`, { method: 'DELETE' })
      setCategories((prev) => prev.filter((c) => c.id !== categorie.id))
      if (categorieEnEdition?.id === categorie.id) setCategorieEnEdition(null)
      invaliderCache('GET:/api/admin/categories')
      invaliderCache('GET:/api/categories')
    } catch (e) {
      setErreur(e instanceof Error ? e.message : 'Erreur')
    }
  }

  const apresCreationCoupon = () => {
    setSousOngletBons('liste')
    setPageBons(1)
    invaliderCache('GET:/api/coupons')
    setRafraichirBons((n) => n + 1)
  }

  const apresModificationCoupon = (coupon: BonReduction) => {
    setBons((prev) => prev.map((b) => (b.id === coupon.id ? coupon : b)))
    setCouponEnEdition(null)
    invaliderCache('GET:/api/coupons')
  }

  const supprimerCoupon = async (coupon: BonReduction) => {
    if (!window.confirm(`Supprimer le coupon « ${coupon.code} » ?`)) return
    try {
      await api(`/api/coupons/${coupon.id}`, { method: 'DELETE' })
      setCouponEnEdition(null)
      invaliderCache('GET:/api/coupons')
      setRafraichirBons((n) => n + 1)
    } catch (e) {
      setErreur(e instanceof Error ? e.message : 'Erreur')
    }
  }

  const changerOnglet = (id: OngletAdmin) => {
    setOnglet(id)
    setSearchParams(id === 'stats' ? {} : { onglet: id }, { replace: true })
    if (id === 'commandes') setPageCommandes(1)
    if (id === 'produits') {
      setPageProduits(1)
      setSousOngletProduits('liste')
      setProduitEnEdition(null)
    }
    if (id === 'categories') setSousOngletCategories('liste')
    setCategorieEnEdition(null)
    if (id === 'utilisateurs') setPageUtilisateurs(1)
    if (id === 'bons') {
      setPageBons(1)
      setSousOngletBons('liste')
      setCouponEnEdition(null)
    }
  }

  const changerStatut = async (id: number, statut: string) => {
    await api(`/api/orders/${id}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ statut }),
    })
    invaliderCache('GET:/api/orders')
    setCommandes((prev) => prev.map((c) => (c.id === id ? { ...c, statut } : c)))
    setCommandeSelectionnee((prev) => (prev?.id === id ? { ...prev, statut } : prev))
  }

  if (authChargement) return <LoadingState />
  if (!estAdmin) return <Navigate to="/connexion" replace state={{ from: '/administration' }} />

  const onglets = [
    { id: 'stats' as const, label: 'Vue d’ensemble', icon: BarChart3 },
    { id: 'commandes' as const, label: 'Commandes', icon: ClipboardList },
    { id: 'produits' as const, label: 'Produits', icon: Package },
    { id: 'categories' as const, label: 'Catégories', icon: Tags },
    { id: 'utilisateurs' as const, label: 'Utilisateurs', icon: Users },
    { id: 'bons' as const, label: 'Promotions', icon: TicketPercent },
  ]
  const titreOnglet = onglets.find((item) => item.id === onglet)?.label ?? 'Administration'

  return (
    <div>
      <PageTitle title="Administration" subtitle="Pilotez les activités essentielles de votre boutique." />
      {chargementInitial && <LoadingState />}

      {!chargementInitial && (
        <div className="grid gap-6 lg:grid-cols-[230px_minmax(0,1fr)]">
          <aside className="h-fit rounded-2xl border border-border bg-surface p-3">
            <p className="px-3 py-2 text-[10px] font-bold uppercase tracking-[0.18em] text-muted">
              Espace admin
            </p>
            <nav className="flex gap-1 overflow-x-auto lg:flex-col" aria-label="Sections d’administration">
              {onglets.map((o) => (
                <button
                  key={o.id}
                  type="button"
                  onClick={() => changerOnglet(o.id)}
                  aria-pressed={onglet === o.id}
                  className={`flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold transition-colors lg:w-full ${
                    onglet === o.id
                      ? 'bg-brand-300 text-brand-600 shadow-sm'
                      : 'text-muted hover:bg-surface-muted hover:text-heading'
                  }`}
                >
                  <o.icon size={17} aria-hidden="true" />
                  <span>{o.label}</span>
                </button>
              ))}
            </nav>
          </aside>

          <section className="min-w-0">
            <div className="mb-5">
              <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-muted">
                {onglet === 'stats' ? 'En un coup d’œil' : 'Gestion'}
              </p>
              <h2 className="mt-1 font-display text-2xl font-bold text-heading">{titreOnglet}</h2>
            </div>

            {erreur && <ErrorState message={erreur} />}
            {chargementOnglet && ['commandes', 'utilisateurs'].includes(onglet) && <LoadingState />}

            {!chargementOnglet && onglet === 'stats' && stats && (
              <div className="space-y-5">
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                  {[
                    { label: 'Commandes', value: stats.commandes, icon: ClipboardList },
                    { label: 'Chiffre d’affaires', value: formatPrix(stats.chiffreAffaires), icon: BarChart3 },
                    { label: 'Produits', value: stats.produits, icon: Package },
                    { label: 'Utilisateurs', value: stats.utilisateurs, icon: Users },
                  ].map((item) => {
                    const Icon = item.icon

                    return (
                      <article key={item.label} className="rounded-2xl border border-border bg-surface p-5">
                        <div className="flex items-start justify-between gap-3">
                          <p className="text-sm font-medium text-muted">{item.label}</p>
                          <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                            <Icon size={18} aria-hidden="true" />
                          </span>
                        </div>
                        <p className="mt-4 text-2xl font-bold tabular-nums text-heading">{item.value}</p>
                      </article>
                    )
                  })}
                </div>

                <div className="grid gap-5 xl:grid-cols-[1.3fr_1fr]">
                  <section className="rounded-2xl border border-border bg-surface p-5 sm:p-6">
                    <div className="mb-2">
                      <h3 className="text-base font-bold text-heading">Commandes par mois</h3>
                      <p className="mt-1 text-sm text-muted">Volume des six derniers mois</p>
                    </div>
                    <GraphiqueCommandes donnees={stats.commandesParMois} />
                  </section>

                  <section className="rounded-2xl border border-border bg-surface p-5 sm:p-6">
                    <div className="mb-5">
                      <h3 className="text-base font-bold text-heading">État des commandes</h3>
                      <p className="mt-1 text-sm text-muted">Répartition par statut</p>
                    </div>
                    <GraphiqueStatuts statuts={stats.statutsCommandes} />
                  </section>
                </div>

                <section className="rounded-2xl border border-border bg-surface p-5 sm:p-6">
                  <div className="mb-4">
                    <h3 className="text-base font-bold text-heading">Actions rapides</h3>
                    <p className="mt-1 text-sm text-muted">Accédez directement aux tâches courantes.</p>
                  </div>
                  <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    <button
                      type="button"
                      onClick={() => changerOnglet('commandes')}
                      className="group flex min-h-14 items-center justify-between gap-3 rounded-xl border border-border px-4 py-3 text-left text-sm font-semibold text-ink transition-colors hover:border-brand-300 hover:bg-surface-muted"
                    >
                      <span className="flex items-center gap-3"><ClipboardList size={17} aria-hidden="true" />Consulter les commandes</span>
                      <ArrowRight size={16} className="text-muted transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        changerOnglet('produits')
                        setSousOngletProduits('nouveau')
                      }}
                      className="group flex min-h-14 items-center justify-between gap-3 rounded-xl border border-border px-4 py-3 text-left text-sm font-semibold text-ink transition-colors hover:border-brand-300 hover:bg-surface-muted"
                    >
                      <span className="flex items-center gap-3"><Package size={17} aria-hidden="true" />Ajouter un produit</span>
                      <ArrowRight size={16} className="text-muted transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        changerOnglet('bons')
                        setSousOngletBons('nouveau')
                      }}
                      className="group flex min-h-14 items-center justify-between gap-3 rounded-xl border border-border px-4 py-3 text-left text-sm font-semibold text-ink transition-colors hover:border-brand-300 hover:bg-surface-muted"
                    >
                      <span className="flex items-center gap-3"><TicketPercent size={17} aria-hidden="true" />Créer une promotion</span>
                      <ArrowRight size={16} className="text-muted transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                    </button>
                  </div>
                </section>
              </div>
            )}

          {!chargementOnglet && onglet === 'commandes' && (
            <div className="space-y-3">
              <div className="flex flex-wrap justify-end gap-3">
                <label className="flex items-center gap-2 text-sm text-muted">
                  État de la commande
                  <Select
                    className="w-52"
                    value={statutCommandeFiltre}
                    onChange={(e) => {
                      const valeur = e.target.value
                      if (valeur === '') {
                        setStatutCommandeFiltre('')
                      } else if (estStatutCommandeFiltre(valeur)) {
                        setStatutCommandeFiltre(valeur)
                      } else {
                        return
                      }
                      setCommandeSelectionnee(null)
                      setPageCommandes(1)
                    }}
                    aria-label="Filtrer par état de commande"
                  >
                    <option value="">Tous les états</option>
                    {Object.entries(STATUTS_COMMANDE).map(([statut, libelle]) => (
                      <option key={statut} value={statut}>{libelle}</option>
                    ))}
                  </Select>
                </label>
                <label className="flex items-center gap-2 text-sm text-muted">
                  Trier les commandes
                  <Select
                    className="w-52"
                    value={triCommandes}
                    onChange={(e) => {
                      if (!estTriCommandes(e.target.value)) return
                      setTriCommandes(e.target.value)
                      setCommandeSelectionnee(null)
                      setPageCommandes(1)
                    }}
                    aria-label="Trier les commandes"
                  >
                    <option value="recent">Plus récentes</option>
                    <option value="ancien">Plus anciennes</option>
                    <option value="montant_asc">Montant croissant</option>
                    <option value="montant_desc">Montant décroissant</option>
                  </Select>
                </label>
              </div>
              {commandes.length === 0 ? (
                <EmptyState message="Aucune commande pour le moment." />
              ) : (
                commandes.map((cmd) => (
                  <div key={cmd.id} className="card-hover flex flex-wrap items-center justify-between gap-4 p-4">
                    <button
                      type="button"
                      onClick={() => setCommandeSelectionnee(cmd)}
                      className="min-w-[12rem] flex-1 text-left"
                      aria-label={`Afficher les détails de la commande ${cmd.numero}`}
                    >
                      <span className="block font-semibold text-heading">{cmd.numero}</span>
                      <span className="mt-1 block text-sm text-muted">
                        {cmd.utilisateur
                          ? `${cmd.utilisateur.prenom} ${cmd.utilisateur.nom}`
                          : [cmd.prenomInvite, cmd.nomInvite].filter(Boolean).join(' ') || cmd.emailInvite || 'Commande invitée'}
                      </span>
                      <span className="mt-1 block text-price text-sm font-bold">{formatPrix(cmd.total)}</span>
                    </button>
                    <Select
                      className="w-44"
                      value={cmd.statut}
                      onChange={(e) => changerStatut(cmd.id, e.target.value)}
                      aria-label={`Statut de ${cmd.numero}`}
                    >
                      {Object.entries(STATUTS_COMMANDE).map(([k, v]) => (
                        <option key={k} value={k}>{v}</option>
                      ))}
                    </Select>
                  </div>
                ))
              )}
              <Pagination pagination={paginationCommandes} onPageChange={setPageCommandes} />
              {commandeSelectionnee && (
                <dialog
                  ref={dialogueCommandeRef}
                  aria-labelledby="titre-details-commande"
                  onClose={() => setCommandeSelectionnee(null)}
                  onClick={(e) => {
                    if (e.target === e.currentTarget) dialogueCommandeRef.current?.close()
                  }}
                  className="fixed inset-0 m-auto max-h-[90vh] w-[min(42rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border border-border bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50"
                >
                  <div className="flex items-start justify-between gap-4 border-b border-border p-5 sm:p-6">
                    <div>
                      <p className="text-xs font-bold uppercase tracking-wider text-muted">Détails de la commande</p>
                      <h3 id="titre-details-commande" className="mt-1 text-xl font-bold text-heading">{commandeSelectionnee.numero}</h3>
                    </div>
                    <button type="button" onClick={() => dialogueCommandeRef.current?.close()} className="btn-secondary px-3 py-2 text-sm">
                      Fermer
                    </button>
                  </div>
                  <div className="space-y-5 p-5 sm:p-6">
                    <dl className="grid gap-3 text-sm sm:grid-cols-2">
                      <div>
                        <dt className="text-muted">Client</dt>
                        <dd className="font-medium text-heading">
                          {commandeSelectionnee.utilisateur
                            ? `${commandeSelectionnee.utilisateur.prenom} ${commandeSelectionnee.utilisateur.nom}`
                            : [commandeSelectionnee.prenomInvite, commandeSelectionnee.nomInvite].filter(Boolean).join(' ') || 'Client invité'}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-muted">E-mail</dt>
                        <dd className="break-all font-medium text-heading">
                          {commandeSelectionnee.utilisateur?.email ?? commandeSelectionnee.emailInvite ?? 'Non renseigné'}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-muted">Téléphone</dt>
                        <dd className="font-medium text-heading">
                          {commandeSelectionnee.utilisateur?.telephone ?? commandeSelectionnee.telephoneInvite ?? 'Non renseigné'}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-muted">État</dt>
                        <dd className="font-medium text-heading">{STATUTS_COMMANDE[commandeSelectionnee.statut] ?? commandeSelectionnee.statut}</dd>
                      </div>
                      <div>
                        <dt className="text-muted">Date</dt>
                        <dd className="font-medium text-heading">
                          {commandeSelectionnee.dateCreation
                            ? new Date(commandeSelectionnee.dateCreation).toLocaleString('fr-FR')
                            : 'Non renseignée'}
                        </dd>
                      </div>
                      <div className="sm:col-span-2">
                        <dt className="text-muted">Adresse de livraison</dt>
                        <dd className="font-medium text-heading">
                          {commandeSelectionnee.adresseLivraisonComplete
                            || [commandeSelectionnee.adresseLivraison?.rue, commandeSelectionnee.adresseLivraison?.ville].filter(Boolean).join(', ')
                            || 'Non renseignée'}
                        </dd>
                      </div>
                    </dl>

                    <section>
                      <h4 className="mb-2 font-semibold text-heading">Articles commandés</h4>
                      {commandeSelectionnee.lignes.length === 0 ? (
                        <p className="text-sm text-muted">Aucun détail d’article disponible.</p>
                      ) : (
                        <ul className="divide-y divide-border rounded-xl border border-border">
                          {commandeSelectionnee.lignes.map((ligne) => (
                            <li key={ligne.id} className="flex items-start justify-between gap-4 p-3 text-sm">
                              <div>
                                <p className="font-medium text-heading">{ligne.produit?.nom ?? 'Article indisponible'}</p>
                                {ligne.libelleVariante && <p className="text-xs text-muted">{ligne.libelleVariante}</p>}
                                <p className="text-xs text-muted">{ligne.quantite} × {formatPrix(ligne.prixUnitaire)}</p>
                              </div>
                              <p className="shrink-0 font-semibold">{formatPrix(ligne.sousTotal ?? Number(ligne.prixUnitaire) * ligne.quantite)}</p>
                            </li>
                          ))}
                        </ul>
                      )}
                    </section>

                    <dl className="ml-auto grid max-w-xs grid-cols-2 gap-x-5 gap-y-2 text-sm">
                      <dt className="text-muted">Sous-total</dt><dd className="text-right">{formatPrix(commandeSelectionnee.sousTotal)}</dd>
                      <dt className="text-muted">Livraison</dt><dd className="text-right">{formatPrix(commandeSelectionnee.fraisLivraison)}</dd>
                      <dt className="text-muted">Réduction</dt><dd className="text-right">− {formatPrix(commandeSelectionnee.reduction)}</dd>
                      <dt className="border-t border-border pt-2 font-bold text-heading">Total</dt>
                      <dd className="border-t border-border pt-2 text-right font-bold text-heading">{formatPrix(commandeSelectionnee.total)}</dd>
                    </dl>
                  </div>
                </dialog>
              )}
            </div>
          )}

          {onglet === 'produits' && (
            <div>
              <SousOnglets
                onglets={[
                  { id: 'liste', label: `Liste (${paginationProduits.total})` },
                  { id: 'nouveau', label: 'Nouveau produit' },
                ]}
                actif={sousOngletProduits}
                onChange={(id) => {
                  setSousOngletProduits(id)
                  if (id === 'nouveau') setProduitEnEdition(null)
                }}
              />

              {sousOngletProduits === 'liste' && produitEnEdition && (
                <FormModifierProduit
                  produit={produitEnEdition}
                  categories={categories}
                  onAnnuler={() => setProduitEnEdition(null)}
                  onModifie={apresModificationProduit}
                />
              )}

              {sousOngletProduits === 'liste' && (
                <>
                  {chargementOnglet && <LoadingState />}
                  {!chargementOnglet && (
                    <>
                      <div className="mb-3 flex justify-end">
                        <label className="flex items-center gap-2 text-sm text-muted">
                          Trier les produits
                          <Select
                            className="w-52"
                            value={triProduits}
                            onChange={(e) => {
                              if (!estTriProduits(e.target.value)) return
                              setTriProduits(e.target.value)
                              setPageProduits(1)
                            }}
                            aria-label="Trier les produits"
                          >
                            <option value="recent">Plus récents</option>
                            <option value="nom_asc">Nom A–Z</option>
                            <option value="nom_desc">Nom Z–A</option>
                            <option value="prix_asc">Prix croissant</option>
                            <option value="prix_desc">Prix décroissant</option>
                            <option value="stock_asc">Stock croissant</option>
                            <option value="stock_desc">Stock décroissant</option>
                          </Select>
                        </label>
                      </div>
                      <div className="card-hover overflow-x-auto">
                        <table className="w-full text-sm">
                          <thead className="bg-surface-muted">
                            <tr>
                              <th className="p-3 text-left">Nom</th>
                              <th className="p-3 text-left">Prix</th>
                              <th className="p-3 text-left">Stock</th>
                              <th className="p-3 text-left">Catégorie</th>
                              <th className="p-3 text-left">Statut</th>
                              <th className="p-3 text-right">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            {produits.length === 0 && (
                              <tr>
                                <td colSpan={6} className="p-8 text-center text-sm text-muted">Aucun produit trouvé.</td>
                              </tr>
                            )}
                            {produits.map((p) => (
                              <tr
                                key={p.id}
                                className={`border-t border-border ${produitEnEdition?.id === p.id ? 'bg-brand-50/50 dark:bg-brand-950/20' : ''}`}
                              >
                                <td className="p-3">{p.nom}</td>
                                <td className="text-price p-3">{formatPrix(p.prixPromo ?? p.prix)}</td>
                                <td className="p-3">{p.stock}</td>
                                <td className="p-3">{p.categorie?.nom}</td>
                                <td className="p-3">
                                  <span className={`text-xs ${p.actif !== false ? 'text-green-600' : 'text-red-500'}`}>
                                    {p.actif !== false ? 'Actif' : 'Inactif'}
                                  </span>
                                </td>
                                <td className="p-3 text-right">
                                  <button
                                    type="button"
                                    className="btn-secondary inline-flex gap-1.5 px-3 py-1.5 text-xs"
                                    onClick={() => setProduitEnEdition(p)}
                                  >
                                    <Pencil size={14} />
                                    Modifier
                                  </button>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                      <Pagination pagination={paginationProduits} onPageChange={setPageProduits} />
                    </>
                  )}
                </>
              )}

              {sousOngletProduits === 'nouveau' && (
                <FormCreerProduit
                  categories={categories}
                  onCree={apresCreationProduit}
                />
              )}
            </div>
          )}

          {onglet === 'categories' && (
            <div>
              <SousOnglets
                onglets={[
                  { id: 'liste', label: `Liste (${categories.length})` },
                  { id: 'nouveau', label: 'Nouvelle catégorie' },
                ]}
                actif={sousOngletCategories}
                onChange={setSousOngletCategories}
              />

              {sousOngletCategories === 'liste' && (
                <div className="space-y-3">
                  {categorieEnEdition && (
                    <FormModifierCategorie
                      categorie={categorieEnEdition}
                      onAnnuler={() => setCategorieEnEdition(null)}
                      onModifie={apresModificationCategorie}
                    />
                  )}
                  {categories.length === 0 && <EmptyState message="Aucune catégorie. Créez-en une pour organiser vos produits." />}
                  {categories.map((c) => (
                    <div key={c.id} className="card-hover flex flex-wrap items-start justify-between gap-3 p-4">
                      <div>
                        <p className="font-medium">{c.nom}</p>
                        {c.description && <p className="mt-1 text-sm text-muted">{c.description}</p>}
                        <p className="mt-1 text-xs text-muted">/{c.slug}</p>
                      </div>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          className="btn-secondary inline-flex gap-1.5 px-3 py-1.5 text-xs"
                          onClick={() => setCategorieEnEdition(c)}
                        >
                          <Pencil size={14} />
                          Modifier
                        </button>
                        <button
                          type="button"
                          className="btn-secondary inline-flex gap-1.5 px-3 py-1.5 text-xs text-red-600"
                          onClick={() => supprimerCategorie(c)}
                        >
                          <Trash2 size={14} />
                          Supprimer
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {sousOngletCategories === 'nouveau' && (
                <FormCreerCategorie onCree={apresCreationCategorie} />
              )}
            </div>
          )}

          {!chargementOnglet && onglet === 'utilisateurs' && (
            <div className="space-y-2">
              {utilisateurs.length === 0 && <EmptyState message="Aucun utilisateur trouvé." />}
              {utilisateurs.map((u) => (
                <div key={u.id} className="card-hover flex justify-between p-4">
                  <div>
                    <p className="font-medium">{u.prenom} {u.nom}</p>
                    <p className="text-sm text-muted">{u.email}</p>
                  </div>
                  <span className="text-xs text-brand-600">{u.roles?.includes('ROLE_ADMIN') ? 'Admin' : 'Client'}</span>
                </div>
              ))}
              <Pagination pagination={paginationUtilisateurs} onPageChange={setPageUtilisateurs} />
            </div>
          )}

          {onglet === 'bons' && (
            <div>
              <SousOnglets
                onglets={[
                  { id: 'liste', label: `Liste (${paginationBons.total})` },
                  { id: 'nouveau', label: 'Nouveau coupon' },
                ]}
                actif={sousOngletBons}
                onChange={setSousOngletBons}
              />

              {sousOngletBons === 'liste' && (
                <>
                  {chargementOnglet && <LoadingState />}
                  {!chargementOnglet && (
                    <>
                      {couponEnEdition && (
                        <FormModifierCoupon
                          bon={couponEnEdition}
                          onAnnuler={() => setCouponEnEdition(null)}
                          onModifie={apresModificationCoupon}
                        />
                      )}
                      <div className="space-y-3">
                        {bons.length === 0 && <EmptyState message="Aucune promotion créée pour le moment." />}
                        {bons.map((b) => (
                          <div key={b.id} className="card-hover flex flex-wrap items-start justify-between gap-4 p-4">
                            <div>
                              <p className="font-bold tracking-wide">{b.code}</p>
                              <p className="text-price mt-1 text-sm font-semibold">
                                {b.type === 'pourcentage' ? `${b.valeur}%` : formatPrix(b.valeur)}
                              </p>
                              <p className="mt-2 text-xs text-muted">
                                {b.montantMinimum && `Panier min. ${formatPrix(b.montantMinimum)} · `}
                                {b.utilisationsMax != null
                                  ? `${b.utilisations ?? 0}/${b.utilisationsMax} utilisations`
                                  : `${b.utilisations ?? 0} utilisation(s)`}
                                {b.dateFin && ` · expire le ${new Date(b.dateFin).toLocaleDateString('fr-FR')}`}
                              </p>
                            </div>
                            <span className={`rounded-full px-3 py-1 text-xs font-medium ${b.actif ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400'}`}>
                              {b.actif ? 'Actif' : 'Inactif'}
                            </span>
                            <div className="ml-auto flex gap-2">
                              <button
                                type="button"
                                className="btn-secondary inline-flex gap-1.5 px-3 py-1.5 text-xs"
                                onClick={() => setCouponEnEdition(b)}
                              >
                                <Pencil size={14} />
                                Modifier
                              </button>
                              <button
                                type="button"
                                className="btn-secondary inline-flex gap-1.5 px-3 py-1.5 text-xs text-red-600"
                                onClick={() => supprimerCoupon(b)}
                              >
                                <Trash2 size={14} />
                                Supprimer
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                      <Pagination pagination={paginationBons} onPageChange={setPageBons} />
                    </>
                  )}
                </>
              )}

              {sousOngletBons === 'nouveau' && (
                <FormCreerCoupon onCree={apresCreationCoupon} />
              )}
            </div>
          )}
          </section>
        </div>
      )}
    </div>
  )
}
