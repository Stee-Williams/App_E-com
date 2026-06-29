import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { FormField, Input } from '../components/Form'
import { PageTitle } from '../components/Ui'

export default function InscriptionPage() {
  const { inscription } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ prenom: '', nom: '', email: '', motDePasse: '' })
  const [erreur, setErreur] = useState('')
  const [chargement, setChargement] = useState(false)

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    setChargement(true)
    setErreur('')
    try {
      await inscription(form)
      navigate('/')
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur d\'inscription')
    } finally {
      setChargement(false)
    }
  }

  return (
    <div className="mx-auto max-w-md">
      <PageTitle title="Inscription" subtitle="Créez votre compte gratuitement" />
      <form onSubmit={soumettre} className="card-hover space-y-4 p-6">
        <div className="grid grid-cols-2 gap-4">
          <FormField label="Prénom">
            <Input placeholder="Marie" value={form.prenom} onChange={(e) => setForm({ ...form, prenom: e.target.value })} required />
          </FormField>
          <FormField label="Nom">
            <Input placeholder="Dupont" value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required />
          </FormField>
        </div>
        <FormField label="Email">
          <Input type="email" autoComplete="email" placeholder="vous@exemple.com" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
        </FormField>
        <FormField label="Mot de passe" hint="6 caractères minimum">
          <Input type="password" autoComplete="new-password" placeholder="••••••••" value={form.motDePasse} onChange={(e) => setForm({ ...form, motDePasse: e.target.value })} required minLength={6} />
        </FormField>
        {erreur && <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-950/50 dark:text-red-400">{erreur}</p>}
        <button type="submit" className="btn-primary w-full" disabled={chargement}>
          {chargement ? 'Création…' : 'Créer mon compte'}
        </button>
        <p className="text-center text-xs text-muted">
          En créant un compte, vous acceptez nos{' '}
          <Link to="/cgi" className="link-accent">CGI</Link>
          {' '}et nos{' '}
          <Link to="/cgv" className="link-accent">CGV</Link>.
        </p>
        <p className="text-center text-sm text-muted">
          Déjà inscrit ? <Link to="/connexion" className="link-accent">Connexion</Link>
        </p>
      </form>
    </div>
  )
}
