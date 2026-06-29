import { useState } from 'react'
import { Link } from 'react-router-dom'
import { FormField, Input } from '../components/Form'
import { PageTitle } from '../components/Ui'

export default function MotDePasseOubliePage() {
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState('')
  const [erreur, setErreur] = useState('')

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      const { api } = await import('../api/client')
      const res = await api<{ message: string }>('/api/auth/forgot-password', {
        method: 'POST',
        body: JSON.stringify({ email }),
      })
      setMessage(res.message)
      setErreur('')
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur')
    }
  }

  return (
    <div className="mx-auto max-w-md">
      <PageTitle title="Mot de passe oublié" subtitle="Nous vous enverrons un lien de réinitialisation" />
      <form onSubmit={soumettre} className="card-hover space-y-4 p-6">
        <FormField label="Email">
          <Input type="email" autoComplete="email" placeholder="vous@exemple.com" value={email} onChange={(e) => setEmail(e.target.value)} required />
        </FormField>
        {message && <p className="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 dark:bg-green-950/40 dark:text-green-400">{message}</p>}
        {erreur && <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-950/50 dark:text-red-400">{erreur}</p>}
        <button type="submit" className="btn-primary w-full">Envoyer le lien</button>
        <Link to="/connexion" className="link-accent block text-center text-sm">Retour à la connexion</Link>
      </form>
    </div>
  )
}
