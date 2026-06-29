import { useState } from 'react'
import { FormField, Input, Textarea } from '../components/Form'
import { PageTitle } from '../components/Ui'

export default function ContactPage() {
  const [envoye, setEnvoye] = useState(false)

  return (
    <div className="mx-auto max-w-lg">
      <PageTitle title="Contact" subtitle="Une question ? Écrivez-nous." />
      {envoye ? (
        <div className="card-hover p-6 text-center text-green-700 dark:text-green-400">
          Message envoyé ! Nous vous répondrons sous 24 h.
        </div>
      ) : (
        <form
          onSubmit={(e) => { e.preventDefault(); setEnvoye(true) }}
          className="card-hover space-y-4 p-6"
        >
          <FormField label="Nom complet">
            <Input placeholder="Votre nom" required />
          </FormField>
          <FormField label="Email">
            <Input type="email" placeholder="vous@exemple.com" required />
          </FormField>
          <FormField label="Message">
            <Textarea placeholder="Décrivez votre demande…" required />
          </FormField>
          <button type="submit" className="btn-primary w-full">Envoyer</button>
        </form>
      )}
    </div>
  )
}
