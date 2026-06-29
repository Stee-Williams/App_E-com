import { useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { FormField, Input } from '../components/Form'
import { PageTitle } from '../components/Ui'

export default function ConnexionPage() {
  const { connexion } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const retour = (location.state as { from?: string } | null)?.from ?? '/'
  const [email, setEmail] = useState('')
  const [motDePasse, setMotDePasse] = useState('')
  const [erreur, setErreur] = useState('')
  const [chargement, setChargement] = useState(false)

  const soumettre = async (e: React.FormEvent) => {
    e.preventDefault()
    setChargement(true)
    setErreur('')
    try {
      const utilisateur = await connexion(email, motDePasse)
      const estAdmin = utilisateur.roles?.includes('ROLE_ADMIN') ?? false
      const destination = estAdmin && retour === '/' ? '/administration' : retour
      navigate(destination)
    } catch (err) {
      setErreur(err instanceof Error ? err.message : 'Erreur de connexion')
    } finally {
      setChargement(false)
    }
  }

  return (
    <div className="mx-auto max-w-md">
      <PageTitle title="Connexion" subtitle={retour === '/administration' ? 'Connectez-vous avec un compte administrateur' : 'Accédez à votre compte NovaShop'} />
      <form onSubmit={soumettre} className="card-hover space-y-4 p-6">
        <FormField label="Adresse email">
          <Input
            type="email"
            autoComplete="email"
            placeholder="vous@exemple.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
        </FormField>
        <FormField label="Mot de passe">
          <Input
            type="password"
            autoComplete="current-password"
            placeholder="••••••••"
            value={motDePasse}
            onChange={(e) => setMotDePasse(e.target.value)}
            required
          />
        </FormField>
        {erreur && <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-950/50 dark:text-red-400">{erreur}</p>}
        <button type="submit" className="btn-primary w-full" disabled={chargement}>
          {chargement ? 'Connexion…' : 'Se connecter'}
        </button>
        <p className="text-center text-sm text-muted">
          <Link to="/mot-de-passe-oublie" className="link-accent">Mot de passe oublié ?</Link>
        </p>
        <p className="text-center text-sm text-muted">
          Pas de compte ? <Link to="/inscription" className="link-accent">Créer un compte</Link>
        </p>
      </form>
      <p className="mt-4 text-center text-xs text-muted">Démo : admin@novashop.fr / admin123</p>
    </div>
  )
}
