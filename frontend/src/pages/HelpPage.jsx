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
  Headphones,
  ClipboardList,
  FileSearch,
  CheckCircle,
  AlertTriangle,
  Globe,
} from 'lucide-react'

const steps = [
  {
    title: 'Valider votre identite',
    description: 'Saisissez votre email institutionnel, numero Apogee et CIN pour acceder au formulaire.',
    icon: BadgeCheck,
    meta: '2 min',
  },
  {
    title: 'Choisir le document',
    description: 'Selectionnez le type de document et completez les champs demandes.',
    icon: FileText,
    meta: '3 min',
  },
  {
    title: 'Suivre la demande',
    description: 'Recuperez le numero de demande pour suivre le statut en temps reel.',
    icon: Clock,
    meta: '24-48h',
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
  'Evitez les caracteres speciaux dans les champs libres.',
]

export default function HelpPage() {
  const navigate = useNavigate()

  return (
    <div className="help-page min-h-screen bg-[#0b1020] text-slate-100">
      <div className="relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(125,211,252,0.16),_transparent_55%),radial-gradient(circle_at_20%_40%,_rgba(253,230,138,0.14),_transparent_45%),radial-gradient(circle_at_85%_20%,_rgba(167,243,208,0.14),_transparent_50%)]" />
        <div className="absolute inset-0 opacity-60 bg-[linear-gradient(120deg,_rgba(5,8,20,0.98)_0%,_rgba(16,24,39,0.82)_40%,_rgba(20,83,45,0.25)_100%)]" />

        <div className="relative max-w-6xl mx-auto px-6 py-10">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <button
              onClick={() => navigate('/')}
              className="inline-flex items-center gap-2 text-sm text-slate-200 hover:text-white"
            >
              <ArrowLeft className="w-4 h-4" />
              Retour a l accueil
            </button>
            <div className="flex items-center gap-3 text-xs text-slate-300">
              <span className="inline-flex items-center gap-2 rounded-full border border-slate-700 px-3 py-1">
                <Globe className="w-3 h-3" />
                Service en ligne
              </span>
              <span className="inline-flex items-center gap-2 rounded-full border border-slate-700 px-3 py-1">
                <Headphones className="w-3 h-3" />
                Support actif
              </span>
            </div>
          </div>

          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="mt-10"
          >
            <div className="flex items-center gap-3 text-teal-200 uppercase tracking-[0.3em] text-xs">
              <Sparkles className="w-4 h-4" />
              Centre d aide
            </div>
            <h1 className="help-serif mt-4 text-4xl md:text-5xl font-semibold text-white">
              Un guide clair pour obtenir vos documents sans blocage.
            </h1>
            <p className="mt-4 max-w-2xl text-slate-200">
              Ce centre vous aide a preparer vos demandes, suivre leur progression et corriger les erreurs rapidement.
            </p>
            <div className="mt-8 flex flex-wrap gap-4">
              <button
                onClick={() => navigate('/')}
                className="inline-flex items-center gap-2 rounded-full bg-teal-400/20 px-5 py-2 text-sm font-semibold text-teal-100 ring-1 ring-teal-300/30 hover:bg-teal-400/30"
              >
                <ClipboardList className="w-4 h-4" />
                Demarrer une demande
              </button>
              <button
                onClick={() => navigate('/about')}
                className="inline-flex items-center gap-2 rounded-full border border-slate-700 px-5 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500"
              >
                <LifeBuoy className="w-4 h-4" />
                A propos du service
              </button>
            </div>
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
                className="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-[0_20px_60px_-40px_rgba(45,212,191,0.6)]"
              >
                <div className="flex items-center justify-between">
                  <span className="inline-flex h-11 w-11 items-center justify-center rounded-full bg-teal-500/20 text-teal-200">
                    <Icon className="w-5 h-5" />
                  </span>
                  <span className="text-xs text-slate-400">Etape {index + 1}</span>
                </div>
                <h3 className="mt-4 text-lg font-semibold text-white">{step.title}</h3>
                <p className="mt-2 text-sm text-slate-300">{step.description}</p>
                <div className="mt-4 inline-flex items-center gap-2 text-xs text-teal-200">
                  <Clock className="w-3 h-3" />
                  {step.meta}
                </div>
              </motion.div>
            )
          })}
        </section>

        <section className="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
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

          <div className="space-y-6">
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
            </div>

            <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
              <div className="flex items-center gap-3 text-amber-200">
                <AlertTriangle className="w-5 h-5" />
                <h3 className="text-lg font-semibold text-white">Checklist avant envoi</h3>
              </div>
              <ul className="mt-4 space-y-3 text-sm text-slate-200">
                <li className="flex gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-300" />
                  Email et Apogee identiques a ceux de la scolarite.
                </li>
                <li className="flex gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-300" />
                  Type de document et niveau selectionnes.
                </li>
                <li className="flex gap-2">
                  <CheckCircle className="w-4 h-4 text-teal-300" />
                  Informations de stage completes si besoin.
                </li>
              </ul>
            </div>
          </div>
        </section>

        <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
          <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-8">
            <div className="flex items-center gap-3 text-teal-200">
              <FileSearch className="w-5 h-5" />
              <h2 className="text-xl font-semibold text-white">Legend des statuts</h2>
            </div>
            <div className="mt-6 space-y-4 text-sm text-slate-200">
              <div className="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3">
                <span>En attente</span>
                <span className="text-xs text-amber-200">Verification en cours</span>
              </div>
              <div className="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3">
                <span>Acceptee</span>
                <span className="text-xs text-sky-200">Traitement administratif</span>
              </div>
              <div className="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3">
                <span>Traitee</span>
                <span className="text-xs text-teal-200">Document envoye</span>
              </div>
              <div className="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3">
                <span>Refusee</span>
                <span className="text-xs text-rose-200">Motif a corriger</span>
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/80 to-emerald-900/30 p-8">
            <div className="flex items-center gap-3 text-teal-200">
              <Mail className="w-5 h-5" />
              <h2 className="text-xl font-semibold text-white">Besoin d un contact direct ?</h2>
            </div>
            <p className="mt-3 text-sm text-slate-200">
              Ecrivez a <span className="text-white font-semibold">support@ens-services.ma</span> en indiquant
              votre numero de demande et le type de document.
            </p>
            <div className="mt-6 grid gap-4 md:grid-cols-2">
              <div className="rounded-xl border border-teal-400/30 bg-teal-500/10 p-4">
                <p className="text-sm font-semibold text-teal-100">Heures de reponse</p>
                <p className="mt-1 text-sm text-slate-200">Du lundi au vendredi, 09:00 - 17:00</p>
              </div>
              <div className="rounded-xl border border-amber-400/30 bg-amber-500/10 p-4">
                <p className="text-sm font-semibold text-amber-100">Delai moyen</p>
                <p className="mt-1 text-sm text-slate-200">Moins de 24h pour les urgences documentaires.</p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  )
}
