import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, apiGetCached } from '../api/client'
import { TTL, invaliderCache } from '../api/cache'
import type { Adresse } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { FormField, Input } from '../components/Form'
import { EmptyState, PageTitle } from '../components/Ui'

export default function ProfilPage() {
  const { utilisateur, estConnecte, jetonPresent, chargement: authChargement } = useAuth()
  const [adresses, setAdresses] = useState<Adresse[]>([])
  const [form, setForm] = useState({ libelle: '', rue: '', ville: '', codePostal: '', pays: 'France', parDefaut: false })
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!jetonPresent) return
    const ctrl = new AbortController()
    apiGetCached<Adresse[]>('/api/addresses', TTL.utilisateur, ctrl.signal)
      .then(setAdresses)
      .catch(() => {})
    return () => ctrl.abort()
  }, [jetonPresent])

  const ajouterAdresse = async (e: React.FormEvent) => {
    e.preventDefault()
    const adresse = await api<Adresse>('/api/addresses', {
      method: 'POST',
      body: JSON.stringify(form),
    })
    setAdresses((prev) => [...prev, adresse])
    setForm({ libelle: '', rue: '', ville: '', codePostal: '', pays: 'France', parDefaut: false })
    setMessage('Adresse ajoutée')
    invaliderCache('GET:/api/addresses')
  }

  const supprimerAdresse = async (id: number) => {
    await api(`/api/addresses/${id}`, { method: 'DELETE' })
    setAdresses((prev) => prev.filter((a) => a.id !== id))
  }

  if ((!jetonPresent || !estConnecte || !utilisateur) && !authChargement) {
    return (
      <div>
        <PageTitle title="Mon profil" />
        <EmptyState message="Connectez-vous pour accéder à votre profil." action={<Link to="/connexion" className="btn-primary">Connexion</Link>} />
      </div>
    )
  }

  if (!utilisateur) return <PageTitle title="Mon profil" />

  return (
    <div className="grid gap-8 lg:grid-cols-2">
      <div>
        <PageTitle title="Mon profil" />
        <div className="card-hover p-6">
          <p className="text-lg font-bold">{utilisateur.prenom} {utilisateur.nom}</p>
          <p className="text-muted">{utilisateur.email}</p>
          {utilisateur.telephone && <p className="mt-1 text-sm">{utilisateur.telephone}</p>}
          <Link to="/commandes" className="btn-secondary mt-4 inline-flex">Mes commandes</Link>
        </div>
      </div>

      <div>
        <h2 className="mb-4 text-xl font-bold">Mes adresses</h2>
        <div className="space-y-3">
          {adresses.map((a) => (
            <div key={a.id} className="card-hover flex justify-between p-4">
              <div>
                <p className="font-medium">{a.libelle} {a.parDefaut && <span className="text-xs text-brand-600">(par défaut)</span>}</p>
                <p className="text-sm text-muted">{a.rue}, {a.codePostal} {a.ville}, {a.pays}</p>
                <p className="text-xs text-muted">ID : {a.id}</p>
              </div>
              <button type="button" onClick={() => supprimerAdresse(a.id)} className="text-sm text-red-500">Supprimer</button>
            </div>
          ))}
        </div>

        <form onSubmit={ajouterAdresse} className="card-hover mt-6 space-y-4 p-6">
          <h3 className="font-semibold">Ajouter une adresse</h3>
          <FormField label="Libellé">
            <Input placeholder="Domicile, Bureau…" value={form.libelle} onChange={(e) => setForm({ ...form, libelle: e.target.value })} required />
          </FormField>
          <FormField label="Rue">
            <Input placeholder="12 avenue de la République" value={form.rue} onChange={(e) => setForm({ ...form, rue: e.target.value })} required />
          </FormField>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="Ville">
              <Input placeholder="Abidjan" value={form.ville} onChange={(e) => setForm({ ...form, ville: e.target.value })} required />
            </FormField>
            <FormField label="Code postal">
              <Input placeholder="00225" value={form.codePostal} onChange={(e) => setForm({ ...form, codePostal: e.target.value })} required />
            </FormField>
          </div>
          <FormField label="Pays">
            <Input placeholder="Côte d'Ivoire" value={form.pays} onChange={(e) => setForm({ ...form, pays: e.target.value })} required />
          </FormField>
          <label className="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" className="rounded border-border text-brand-600 focus:ring-brand-500" checked={form.parDefaut} onChange={(e) => setForm({ ...form, parDefaut: e.target.checked })} />
            Définir comme adresse par défaut
          </label>
          <button type="submit" className="btn-primary">Enregistrer l'adresse</button>
          {message && <p className="text-sm text-green-600 dark:text-green-400">{message}</p>}
        </form>
      </div>
    </div>
  )
}
