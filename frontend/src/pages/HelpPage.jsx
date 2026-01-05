import { motion } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import {
  ArrowLeft,
  LifeBuoy,
  Sparkles,
  BadgeCheck,
  FileText,
  Mail,
  Clock,
  ShieldCheck,
} from 'lucide-react'

const steps = [
  {
    title: 'Valider votre identite',
    description: 'Saisissez votre email institutionnel, numero Apogee et CIN pour acceder au formulaire.',
    icon: BadgeCheck,
  },
  {
    title: 'Choisir le document',
    description: 'Selectionnez le type de document et completez les champs demandes.',
    icon: FileText,
  },
  {
    title: 'Suivre la demande',
    description: 'Recuperez le numero de demande pour suivre le statut en temps reel.',
    icon: Clock,
  },
]

const faqs = [
  {
    question: 'Je ne recois pas le document par email.',
    answer: 'Verifiez les dossiers spam et promotions. Assurez-vous que l email correspond au compte etudiant.',
  },
  {
    question: 'Le statut est reste sur "En attente".',
    answer: 'Le traitement peut prendre 24 a 48h selon la charge. Revenez plus tard ou contactez l administration.',
  },
  {
    question: 'Erreur dans un document recu.',
    answer: 'Utilisez le formulaire de reclamation et indiquez le numero d attestation.',
  },
]

const tips = [
  'Utilisez un email institutionnel valide.',
  'Gardez votre numero de demande pour le suivi.',
  'Pour un stage, verifiez les dates et le type (PFA/PFE).',
]

export default function HelpPage() {
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <div className="relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.18),_transparent_55%),radial-gradient(circle_at_20%_40%,_rgba(251,191,36,0.14),_transparent_45%),radial-gradient(circle_at_80%_20%,_rgba(94,234,212,0.16),_transparent_50%)]" />
        <div className="absolute inset-0 opacity-40 bg-[linear-gradient(120deg,_rgba(15,23,42,0.95)_0%,_rgba(30,41,59,0.75)_40%,_rgba(2,132,199,0.2)_100%)]" />

        <div className="relative max-w-6xl mx-auto px-6 py-10">
          <button
            onClick={() => navigate('/')}
            className="inline-flex items-center gap-2 text-sm text-slate-200 hover:text-white"
          >
            <ArrowLeft className="w-4 h-4" />
            Retour a l accueil
          </button>

          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="mt-8"
          >
            <div className="flex items-center gap-3 text-teal-200 uppercase tracking-[0.3em] text-xs">
              <Sparkles className="w-4 h-4" />
              Centre d aide
            </div>
            <h1 className="mt-4 text-4xl md:text-5xl font-semibold text-white">
              Guide rapide pour vos demandes et documents
            </h1>
            <p className="mt-4 max-w-2xl text-slate-200">
              Tout ce qu il faut pour eviter les blocages, suivre une demande et obtenir vos documents sans stress.
            </p>
          </motion.div>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-6 py-12 space-y-12">
        <section className="grid gap-6 md:grid-cols-3">
          {steps.map((step, index) => {
            const Icon = step.icon
            return (
              <motion.div
                key={step.title}
                initial={{ opacity: 0, y: 18 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.3 }}
                transition={{ duration: 0.4, delay: index * 0.1 }}
                className="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-[0_20px_60px_-40px_rgba(59,130,246,0.6)]"
              >
                <div className="flex items-center justify-between">
                  <span className="inline-flex h-11 w-11 items-center justify-center rounded-full bg-teal-500/20 text-teal-200">
                    <Icon className="w-5 h-5" />
                  </span>
                  <span className="text-xs text-slate-400">Etape {index + 1}</span>
                </div>
                <h3 className="mt-4 text-lg font-semibold text-white">{step.title}</h3>
                <p className="mt-2 text-sm text-slate-300">{step.description}</p>
              </motion.div>
            )
          })}
        </section>

        <section className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
          <div className="rounded-2xl border border-slate-800 bg-slate-900/80 p-8">
            <div className="flex items-center gap-3 text-teal-200">
              <LifeBuoy className="w-5 h-5" />
              <h2 className="text-xl font-semibold text-white">Questions frequentes</h2>
            </div>
            <div className="mt-6 space-y-5">
              {faqs.map((item) => (
                <div key={item.question} className="rounded-xl border border-slate-800/80 bg-slate-950/40 p-4">
                  <p className="text-sm font-semibold text-white">{item.question}</p>
                  <p className="mt-2 text-sm text-slate-300">{item.answer}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/80 to-teal-900/30 p-8">
            <div className="flex items-center gap-3 text-teal-200">
              <ShieldCheck className="w-5 h-5" />
              <h2 className="text-xl font-semibold text-white">Bonnes pratiques</h2>
            </div>
            <ul className="mt-6 space-y-3 text-sm text-slate-200">
              {tips.map((tip) => (
                <li key={tip} className="flex gap-3">
                  <span className="mt-1 h-2 w-2 rounded-full bg-amber-300" />
                  <span>{tip}</span>
                </li>
              ))}
            </ul>
            <div className="mt-8 rounded-xl border border-teal-400/30 bg-teal-500/10 p-4">
              <div className="flex items-center gap-2 text-teal-200">
                <Mail className="w-4 h-4" />
                <p className="text-sm font-semibold">Besoin d un contact direct ?</p>
              </div>
              <p className="mt-2 text-sm text-slate-200">
                Ecrivez a <span className="text-white font-semibold">support@ens-services.ma</span> avec votre numero de demande.
              </p>
            </div>
          </div>
        </section>
      </div>
    </div>
  )
}
