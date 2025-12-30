import { useState, useEffect, useRef } from 'react'
import { motion, AnimatePresence, useScroll, useTransform } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import { 
  FileText, 
  Search, 
  Shield, 
  GraduationCap, 
  CheckCircle, 
  Clock, 
  XCircle,
  X,
  Mail,
  AlertCircle,
  Info
} from 'lucide-react'
import { demandeService, niveauService, anneeService, reclamationService } from '../services/api'

// Nom de l'application et de l'école
const APP_NAME = 'UnivDocs'
const UNIVERSITY_NAME = 'Université Cité des Sciences'
const SCHOOL_NAME = 'École Supérieure d’Ingénierie NovaTech'
const SCHOOL_ACRONYM = 'NovaTech'

export default function HomePage() {
  const navigate = useNavigate()
  const heroRef = useRef(null)
  const formsRef = useRef(null)
  const [activeTab, setActiveTab] = useState('demande') // 'demande' ou 'suivi'
  const [formData, setFormData] = useState({
    email: '',
    apogee_number: '',
    cin: '',
    document_type: '',
    numero_attestation: '',
    description: '',
    additional_info: {}
  })
  const [errors, setErrors] = useState({})
  const [success, setSuccess] = useState(false)
  const [loading, setLoading] = useState(false)
  const [isValidated, setIsValidated] = useState(false) // État pour savoir si l'étudiant est validé
  const [validating, setValidating] = useState(false) // État pour le chargement de la validation
  const [validationError, setValidationError] = useState(null) // Erreur de validation
  const [suiviData, setSuiviData] = useState(null)
  const [suiviLoading, setSuiviLoading] = useState(false)
  const [suiviError, setSuiviError] = useState(null)
  const [niveaux, setNiveaux] = useState([])
  const [loadingNiveaux, setLoadingNiveaux] = useState(true)
  const [annees, setAnnees] = useState([])
  const [loadingAnnees, setLoadingAnnees] = useState(true)

  // Charger les niveaux depuis l'API
  useEffect(() => {
    const loadNiveaux = async () => {
      try {
        const response = await niveauService.getAll()
        if (response.success && response.niveaux) {
          setNiveaux(response.niveaux)
        }
      } catch (error) {
        console.error('Erreur lors du chargement des niveaux:', error)
      } finally {
        setLoadingNiveaux(false)
      }
    }
    loadNiveaux()
  }, [])

  // Charger les années depuis l'API
  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await anneeService.getAll()
        if (response.success && response.annees) {
          setAnnees(response.annees)
        }
      } catch (error) {
        console.error('Erreur lors du chargement des années:', error)
      } finally {
        setLoadingAnnees(false)
      }
    }
    loadAnnees()
  }, [])

  const releveNiveauOptions = [
    { value: '', label: 'Sélectionnez le niveau' },
    { value: '2AP1', label: '2AP1' },
    { value: '2AP2', label: '2AP2' },
    { value: 'CI1', label: 'CI1' },
    { value: 'CI2', label: 'CI2' },
    { value: 'CI3', label: 'CI3' },
  ]
  const attestationNiveauOptions = [
    { value: '', label: 'Sélectionnez le niveau' },
    { value: '2AP1', label: '2AP1' },
    { value: '2AP2', label: '2AP2' },
    { value: 'CI1', label: 'CI1' },
    { value: 'CI2', label: 'CI2' },
    { value: 'CI3', label: 'CI3' },
  ]

  // Types de documents (sans réclamation - elle est gérée séparément)
  const documentTypes = [
    { value: 'Attestation de scolarité', label: 'Attestation de scolarité', fields: [] },
    { 
      value: 'Attestation de réussite', 
      label: 'Attestation de réussite', 
      fields: [
        { name: 'niveau', label: 'Niveau', type: 'select', options: attestationNiveauOptions, placeholder: 'Sélectionnez le niveau', required: true }
      ]
    },
    { 
      value: 'Relevé de notes', 
      label: 'Relevé de notes', 
      fields: [
        { name: 'niveau_cible', label: 'Niveau (jusqu’où générer le relevé)', type: 'select', options: releveNiveauOptions, placeholder: 'Sélectionnez le niveau', required: true }
      ]
    },
    { 
      value: 'Convention de stage', 
      label: 'Convention de stage', 
      fields: [
        // Type de stage
        { name: 'type_stage', label: 'Type de stage', type: 'select', options: [
          { value: '', label: 'Sélectionnez le type' },
          { value: 'PFA', label: 'PFA (Projet de Fin d\'Année) - 4ème année' },
          { value: 'PFE', label: 'PFE (Projet de Fin d\'Études) - 5ème année' }
        ], placeholder: 'Sélectionnez le type', required: true },
        
        // Informations entreprise
        { name: 'nom_entreprise', label: 'Nom de l\'entreprise', type: 'text', placeholder: 'Nom de l\'entreprise', required: true },
        { name: 'adresse_entreprise', label: 'Adresse de l\'entreprise', type: 'text', placeholder: 'Adresse complète de l\'entreprise', required: true },
        { name: 'tel_entreprise', label: 'Téléphone de l\'entreprise', type: 'tel', placeholder: 'Ex: +212 5 39 68 80 27', required: true },
        { name: 'email_entreprise', label: 'Email de l\'entreprise', type: 'email', placeholder: 'email@entreprise.com', required: true },
        { name: 'representant_entreprise', label: 'Représentant de l\'entreprise', type: 'text', placeholder: 'Nom du représentant', required: true },
        { name: 'qualite_representant', label: 'Qualité du représentant', type: 'text', placeholder: 'Ex: Directeur, Responsable RH, etc.', required: true },
        
        // Informations stage
        { name: 'date_debut', label: 'Date de début du stage', type: 'date', placeholder: 'Date de début', required: true },
        { name: 'date_fin', label: 'Date de fin du stage', type: 'date', placeholder: 'Date de fin', required: true },
        { name: 'encadrant', label: 'Encadrant (entreprise)', type: 'text', placeholder: 'Nom de l\'encadrant dans l\'entreprise', required: true },
        { name: 'tuteur', label: 'Tuteur pédagogique (école)', type: 'text', placeholder: 'Nom du tuteur pédagogique', required: true },
        { name: 'theme_stage', label: 'Thème du stage', type: 'textarea', placeholder: 'Décrivez le thème/sujet du stage', required: true }
      ]
    },
    { value: 'Réclamation', label: 'Réclamation', fields: [] }
  ]

  const selectedDocType = documentTypes.find(dt => dt.value === formData.document_type)
  const isReclamation = formData.document_type === 'Réclamation'
  
  // Options de motif pour réclamation
  const motifOptions = [
    'Erreur dans le document',
    'Document incomplet',
    'Informations incorrectes',
    'Document non reçu',
    'Autre'
  ]

  // Validation du formulaire
  const validateForm = () => {
    const newErrors = {}
    
    if (!formData.email) newErrors.email = 'Email requis'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) newErrors.email = 'Email invalide'
    
    if (!formData.apogee_number) newErrors.apogee_number = 'Numéro Apogée requis'
    
    if (!formData.cin) newErrors.cin = 'CIN requis'
    
    if (!formData.document_type) newErrors.document_type = 'Type de document requis'
    
    // Si réclamation, vérifier le numéro d'attestation et la description
    if (isReclamation) {
      if (!formData.numero_attestation) {
        newErrors.numero_attestation = 'Numéro d\'attestation requis pour une réclamation'
      }
      if (!formData.description) {
        newErrors.description = 'Description de la réclamation requise'
      }
    }
    
    // Valider les champs supplémentaires (sauf pour réclamation)
    if (selectedDocType && selectedDocType.fields && !isReclamation) {
      selectedDocType.fields.forEach(field => {
        if (field.required && !formData.additional_info[field.name]) {
          newErrors[field.name] = `${field.label} requis`
        }
      })
    }
    
    return newErrors
  }

  // Validation de l'étudiant (email, CIN, apogee) avant d'accéder au formulaire
  const handleValidate = async (e) => {
    e.preventDefault()
    setValidationError(null)
    setErrors({})
    
    // Validation basique des champs
    if (!formData.email || !formData.apogee_number || !formData.cin) {
      setValidationError('Veuillez remplir tous les champs (email, numéro Apogée, CIN)')
      return
    }
    
    setValidating(true)
    
    try {
      const response = await demandeService.validateStudent(
        formData.email,
        formData.apogee_number,
        formData.cin
      )
      
      if (response.valid) {
        setIsValidated(true)
        setValidationError(null)
      } else {
        setValidationError(response.message || 'Validation échouée')
      }
    } catch (error) {
      setValidationError(
        error?.response?.data?.message || 
        'Erreur lors de la validation. Vérifiez vos informations.'
      )
    } finally {
      setValidating(false)
    }
  }

  // Soumission du formulaire
  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    
    const validationErrors = validateForm()
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors)
      return
    }
    
    setLoading(true)
    
    try {
      // Si c'est une réclamation, créer la réclamation directement
      if (isReclamation) {
        // Créer la réclamation (le backend gère la recherche par numéro)
        await reclamationService.create({
          numero_attestation: formData.numero_attestation,
          motif: formData.additional_info.motif || null, // Optionnel
          description: formData.description
        })
        
        setSuccess(true)
        // Réinitialiser immédiatement le formulaire et la validation
        setFormData({
          email: '',
          apogee_number: '',
          cin: '',
          document_type: '',
          numero_attestation: '',
          description: '',
          additional_info: {}
        })
        setIsValidated(false)
        setErrors({})
        // Masquer le message de succès après 3 secondes
        setTimeout(() => {
          setSuccess(false)
        }, 3000)
        setLoading(false)
        return
      }
      
      const dataToSend = {
        email: formData.email,
        apogee_number: formData.apogee_number,
        cin: formData.cin,
        document_type: formData.document_type,
        additional_info: formData.additional_info
      }
      
      const response = await demandeService.create(dataToSend)
      
      setSuccess(true)
      // Réinitialiser immédiatement le formulaire et la validation
      setFormData({
        email: '',
        apogee_number: '',
        cin: '',
        document_type: '',
        numero_attestation: '',
        description: '',
        additional_info: {}
      })
      setIsValidated(false)
      setErrors({})
      // Masquer le message de succès après 3 secondes
      setTimeout(() => {
        setSuccess(false)
      }, 3000)
    } catch (error) {
      setErrors({ 
        submit: error?.response?.data?.message || 'Erreur lors de la soumission de la demande' 
      })
    } finally {
      setLoading(false)
    }
  }

  // Recherche de suivi
  const handleSuiviSearch = async (e) => {
    e.preventDefault()
    const numero = e.target.numero_demande.value.trim()
    
    if (!numero) {
      setSuiviError('Veuillez entrer un numéro de demande')
      return
    }
    
    setSuiviLoading(true)
    setSuiviError(null)
    setSuiviData(null)
    
    try {
      const data = await demandeService.getByNumero(numero)
      setSuiviData(data)
    } catch (error) {
      setSuiviError(error?.response?.data?.message || 'Demande non trouvée. Vérifiez le numéro de demande.')
    } finally {
      setSuiviLoading(false)
    }
  }

  // Obtenir le badge de statut
  const getStatusBadge = (status) => {
    const statusConfig = {
      'En attente': { icon: Clock, color: 'bg-yellow-100 text-yellow-800', label: 'En attente' },
      'Acceptée': { icon: CheckCircle, color: 'bg-blue-100 text-blue-800', label: 'Acceptée' },
      'Refusée': { icon: XCircle, color: 'bg-red-100 text-red-800', label: 'Refusée' },
      'Traitée': { icon: CheckCircle, color: 'bg-green-100 text-green-800', label: 'Traitée' }
    }
    
    const config = statusConfig[status] || statusConfig['En attente']
    const Icon = config.icon
    
    return (
      <span className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ${config.color}`}>
        <Icon className="w-4 h-4" />
        {config.label}
      </span>
    )
  }

  // Transition visuelle au scroll: le hero "Bienvenue" se masque, les formulaires s'affichent
  const { scrollYProgress } = useScroll({
    target: heroRef,
    offset: ['start start', 'end start'],
  })

  const heroOpacity = useTransform(scrollYProgress, [0, 0.7, 1], [1, 0.35, 0])
  const heroY = useTransform(scrollYProgress, [0, 1], [0, -90])
  const heroScale = useTransform(scrollYProgress, [0, 1], [1, 0.985])

  const mainOpacity = useTransform(scrollYProgress, [0, 0.2, 0.45], [0, 0.5, 1])
  const mainY = useTransform(scrollYProgress, [0, 1], [36, 0])

  const scrollToForms = () => {
    formsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Navbar Professionnelle */}
      <nav className="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Logo et Nom */}
            <div className="flex items-center gap-4">
              <div className="flex items-center justify-center w-12 h-12 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <img src="/logo_novatech_mark.svg" alt="NovaTech" className="w-10 h-10" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-gray-900">{APP_NAME}</h1>
                <p className="text-xs text-gray-600">{SCHOOL_ACRONYM} - {UNIVERSITY_NAME}</p>
              </div>
            </div>
            

            <div className="flex items-center gap-3">
              {/* About */}
              <button
                onClick={() => navigate('/about')}
                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <Info className="w-4 h-4" />
                <span className="hidden sm:inline">About Us</span>
              </button>

              {/* Bouton Admin */}
              <button
                onClick={() => navigate('/admin/login')}
                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
              >
                <Shield className="w-4 h-4" />
                <span className="hidden sm:inline">Administration</span>
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Section Bienvenue avec Animation de Fond Luxueuse */}
      <motion.section
        ref={heroRef}
        style={{ opacity: heroOpacity, y: heroY, scale: heroScale }}
        className="relative overflow-hidden luxury-bg-animation"
      >
        {/* Animation de fond académique professionnelle luxe - Version Premium */}
        <div className="absolute inset-0 overflow-hidden">
          {/* Particules flottantes élégantes */}
          {[...Array(15)].map((_, i) => (
            <motion.div
              key={`particle-${i}`}
              className="absolute w-2 h-2 bg-blue-300/30 rounded-full"
              style={{
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
              }}
              animate={{
                y: [0, -100, -200, -300],
                x: [0, Math.random() * 100 - 50],
                opacity: [0, 1, 1, 0],
                scale: [0.5, 1, 1.2, 0],
              }}
              transition={{
                duration: 8 + Math.random() * 4,
                repeat: Infinity,
                delay: Math.random() * 5,
                ease: "easeOut",
              }}
            />
          ))}
          
          {/* Orbes lumineux animés - Style premium */}
          {/* Orbe principal - Bleu académique avec effet de pulsation */}
          <motion.div
            className="absolute top-0 left-0 w-[500px] h-[500px] bg-gradient-to-br from-blue-400/40 via-blue-300/30 to-indigo-400/40 rounded-full blur-[100px]"
            animate={{
              x: [0, 150, 0],
              y: [0, 100, 0],
              scale: [1, 1.3, 1],
              opacity: [0.3, 0.5, 0.3],
            }}
            transition={{
              duration: 20,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
          
          {/* Orbe secondaire - Indigo avec mouvement fluide */}
          <motion.div
            className="absolute top-20 right-0 w-[450px] h-[450px] bg-gradient-to-br from-indigo-400/35 via-purple-300/25 to-blue-400/35 rounded-full blur-[120px]"
            animate={{
              x: [0, -120, 0],
              y: [0, 150, 0],
              scale: [1, 1.4, 1],
              opacity: [0.25, 0.45, 0.25],
            }}
            transition={{
              duration: 25,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
          
          {/* Orbe tertiaire - Violet avec rotation douce */}
          <motion.div
            className="absolute bottom-0 left-1/3 w-[400px] h-[400px] bg-gradient-to-br from-purple-400/30 via-pink-300/20 to-indigo-400/30 rounded-full blur-[110px]"
            animate={{
              x: [0, 80, 0],
              y: [0, -120, 0],
              scale: [1, 1.5, 1],
              opacity: [0.2, 0.4, 0.2],
            }}
            transition={{
              duration: 30,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
          
          {/* Orbe quaternaire - Cyan avec effet de vague */}
          <motion.div
            className="absolute top-1/2 right-1/4 w-[350px] h-[350px] bg-gradient-to-br from-cyan-400/30 via-blue-300/20 to-indigo-400/30 rounded-full blur-[100px]"
            animate={{
              x: [0, -70, 0],
              y: [0, 90, 0],
              scale: [1, 1.6, 1],
              opacity: [0.2, 0.4, 0.2],
            }}
            transition={{
              duration: 22,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
          
          {/* Orbe quinaire - Bleu profond avec mouvement lent */}
          <motion.div
            className="absolute bottom-1/4 right-0 w-[300px] h-[300px] bg-gradient-to-br from-blue-500/25 via-indigo-400/15 to-purple-400/25 rounded-full blur-[90px]"
            animate={{
              x: [0, 60, 0],
              y: [0, -80, 0],
              scale: [1, 1.4, 1],
              opacity: [0.15, 0.35, 0.15],
            }}
            transition={{
              duration: 28,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
          
          {/* Orbe supplémentaire - Rose pâle pour équilibrer */}
          <motion.div
            className="absolute top-1/3 left-1/2 w-[380px] h-[380px] bg-gradient-to-br from-pink-300/20 via-purple-200/15 to-blue-300/20 rounded-full blur-[105px]"
            animate={{
              x: [0, 100, 0],
              y: [0, -100, 0],
              scale: [1, 1.35, 1],
              opacity: [0.15, 0.3, 0.15],
            }}
            transition={{
              duration: 24,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
          
          {/* Motifs académiques animés - Plus élégants */}
          <motion.div
            className="absolute inset-0 opacity-5"
            animate={{
              opacity: [0.05, 0.08, 0.05],
            }}
            transition={{
              duration: 4,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          >
            <motion.div
              className="absolute top-10 left-10 w-32 h-32 border-2 border-blue-300 rounded-lg"
              animate={{
                rotate: [12, 24, 12],
                scale: [1, 1.1, 1],
              }}
              transition={{
                duration: 8,
                repeat: Infinity,
                ease: "easeInOut",
              }}
            />
            <motion.div
              className="absolute top-40 right-20 w-24 h-24 border-2 border-indigo-300 rounded-lg"
              animate={{
                rotate: [45, 60, 45],
                scale: [1, 1.15, 1],
              }}
              transition={{
                duration: 10,
                repeat: Infinity,
                ease: "easeInOut",
              }}
            />
            <motion.div
              className="absolute bottom-20 left-1/4 w-28 h-28 border-2 border-purple-300 rounded-lg"
              animate={{
                rotate: [-12, 0, -12],
                scale: [1, 1.12, 1],
              }}
              transition={{
                duration: 12,
                repeat: Infinity,
                ease: "easeInOut",
              }}
            />
            <motion.div
              className="absolute top-1/3 right-1/3 w-20 h-20 border-2 border-cyan-300 rounded-lg"
              animate={{
                rotate: [0, 90, 0],
                scale: [1, 1.2, 1],
              }}
              transition={{
                duration: 15,
                repeat: Infinity,
                ease: "easeInOut",
              }}
            />
          </motion.div>
          
          {/* Lignes de grille animées - Style académique premium */}
          <div className="absolute inset-0 opacity-5">
            <svg className="w-full h-full" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <pattern id="grid-luxury" width="60" height="60" patternUnits="userSpaceOnUse">
                  <path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(59, 130, 246, 0.15)" strokeWidth="0.5"/>
                </pattern>
                <linearGradient id="glow-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stopColor="rgba(59, 130, 246, 0.1)" />
                  <stop offset="50%" stopColor="rgba(99, 102, 241, 0.15)" />
                  <stop offset="100%" stopColor="rgba(139, 92, 246, 0.1)" />
                </linearGradient>
              </defs>
              <motion.rect
                width="100%"
                height="100%"
                fill="url(#grid-luxury)"
                animate={{
                  opacity: [0.05, 0.1, 0.05],
                }}
                transition={{
                  duration: 6,
                  repeat: Infinity,
                  ease: "easeInOut",
                }}
              />
              {/* Effet de brillance subtil */}
              <motion.rect
                width="100%"
                height="100%"
                fill="url(#glow-gradient)"
                animate={{
                  opacity: [0.02, 0.05, 0.02],
                }}
                transition={{
                  duration: 8,
                  repeat: Infinity,
                  ease: "easeInOut",
                }}
              />
            </svg>
          </div>
          
          {/* Effet de lumière rayonnante au centre */}
          <motion.div
            className="absolute top-1/2 left-1/2 w-[600px] h-[600px] -translate-x-1/2 -translate-y-1/2 bg-gradient-radial from-blue-200/20 via-transparent to-transparent rounded-full blur-[150px]"
            animate={{
              scale: [1, 1.2, 1],
              opacity: [0.1, 0.2, 0.1],
            }}
            transition={{
              duration: 10,
              repeat: Infinity,
              ease: "easeInOut",
            }}
          />
        </div>

        {/* Contenu de la section bienvenue */}
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center"
          >
            <motion.div
              initial={{ scale: 0.9 }}
              animate={{ scale: 1 }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="inline-block mb-4"
            >
              <div className="flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg mx-auto mb-4">
                <GraduationCap className="w-10 h-10 text-white" />
              </div>
            </motion.div>
            
            <motion.h1
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.3 }}
              whileHover={{
                y: -2,
                scale: 1.015,
                textShadow: '0px 14px 28px rgba(37, 99, 235, 0.18)',
              }}
              whileTap={{ scale: 0.995 }}
              className="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-4 cursor-default select-none"
            >
              Bienvenue sur <span className="text-blue-600">{APP_NAME}</span>
            </motion.h1>
            
            <motion.p
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.4 }}
              className="text-lg md:text-xl text-gray-600 mb-2 max-w-2xl mx-auto"
            >
              Portail de Gestion Documentaire Académique
            </motion.p>
            
            <motion.p
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.5 }}
              className="text-base text-gray-500 max-w-xl mx-auto"
            >
              {SCHOOL_NAME} - {UNIVERSITY_NAME}
            </motion.p>
            
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ duration: 0.6, delay: 0.6 }}
              className="mt-8 flex flex-wrap justify-center gap-4"
            >
              <div className="flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-lg shadow-sm">
                <FileText className="w-5 h-5 text-blue-600" />
                <span className="text-sm text-gray-700">Documents en ligne</span>
              </div>
              <div className="flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-lg shadow-sm">
                <Mail className="w-5 h-5 text-blue-600" />
                <span className="text-sm text-gray-700">Réception par email</span>
              </div>
              <div className="flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-lg shadow-sm">
                <CheckCircle className="w-5 h-5 text-blue-600" />
                <span className="text-sm text-gray-700">Suivi en temps réel</span>
              </div>
            </motion.div>

            {/* CTA scroll (transition vers formulaires) */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ duration: 0.6, delay: 0.75 }}
              className="mt-10 flex justify-center"
            >
              <button
                onClick={scrollToForms}
                className="group inline-flex items-center gap-3 px-5 py-3 rounded-full bg-white/80 backdrop-blur-sm border border-white/60 shadow-sm hover:shadow-md transition-all"
              >
                <span className="text-sm font-semibold text-gray-900">Accéder aux formulaires</span>
                <span className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white group-hover:translate-y-0.5 transition-transform">
                  <svg viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                    <path
                      fillRule="evenodd"
                      d="M10 14a1 1 0 0 1-.707-.293l-5-5a1 1 0 1 1 1.414-1.414L10 11.586l4.293-4.293a1 1 0 1 1 1.414 1.414l-5 5A1 1 0 0 1 10 14z"
                      clipRule="evenodd"
                    />
                  </svg>
                </span>
              </button>
            </motion.div>
          </motion.div>
        </div>
      </motion.section>

      {/* Contenu principal */}
      <motion.main
        ref={formsRef}
        style={{ opacity: mainOpacity, y: mainY }}
        className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 scroll-mt-24"
      >
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Carte 1: Nouvelle Demande */}
          <div className="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <FileText className="w-5 h-5 text-white" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-gray-900">Nouvelle Demande</h2>
                <p className="text-sm text-gray-500">Créez une demande de document</p>
              </div>
            </div>

            {success ? (
              <div className="text-center py-6">
                <CheckCircle className="w-12 h-12 text-green-500 mx-auto mb-3" />
                <h3 className="text-lg font-semibold text-gray-900 mb-2">Demande créée avec succès !</h3>
                <p className="text-sm text-gray-600">Vous recevrez un email avec le numéro de demande</p>
                <button
                  onClick={() => {
                    setSuccess(false)
                    setIsValidated(false)
                    setFormData({
                      email: '',
                      apogee_number: '',
                      cin: '',
                      document_type: '',
                      numero_attestation: '',
                      description: '',
                      additional_info: {}
                    })
                  }}
                  className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                  Nouvelle demande
                </button>
              </div>
            ) : (
              // Formulaire avec validation (champs toujours visibles et modifiables)
              <div className="space-y-5">
                {/* Section de validation */}
                <form onSubmit={handleValidate} className="space-y-5">
                  {!isValidated && (
                    <div className="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg mb-4">
                      <p className="text-sm text-blue-700">
                        <strong>Étape 1 :</strong> Veuillez d'abord valider vos informations pour accéder au formulaire de demande.
                      </p>
                    </div>
                  )}
                  
                  {/* Email */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Email <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      disabled={validating}
                      className={`w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                        errors.email ? 'border-red-500' : 'border-gray-300'
                      } ${validating ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                      placeholder="votre.email@gmail.com"
                      required
                    />
                    {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                  </div>

                  {/* Numéro Apogée */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Numéro Apogée <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={formData.apogee_number}
                      onChange={(e) => setFormData({ ...formData, apogee_number: e.target.value.toUpperCase() })}
                      disabled={validating}
                      className={`input-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                        errors.apogee_number ? 'border-red-500' : 'border-gray-300'
                      } ${validating ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                      placeholder="A12345"
                      required
                    />
                    {errors.apogee_number && <p className="mt-1 text-sm text-red-600">{errors.apogee_number}</p>}
                  </div>

                  {/* CIN */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      CIN <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={formData.cin}
                      onChange={(e) => setFormData({ ...formData, cin: e.target.value.toUpperCase() })}
                      disabled={validating}
                      className={`input-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                        errors.cin ? 'border-red-500' : 'border-gray-300'
                      } ${validating ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                      placeholder="AB123456"
                      required
                    />
                    {errors.cin && <p className="mt-1 text-sm text-red-600">{errors.cin}</p>}
                  </div>

                  {validationError && (
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                      <p className="text-sm text-red-700">{validationError}</p>
                    </div>
                  )}

                  <button
                    type="submit"
                    disabled={validating}
                    className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {validating ? 'Validation en cours...' : 'Valider mes informations'}
                  </button>
                </form>

                {/* Formulaire de demande (affiché seulement si validé) */}
                {isValidated && (
                  <form onSubmit={handleSubmit} className="space-y-5 mt-6 pt-6 border-t border-gray-200">
                    <div className="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg mb-4">
                      <p className="text-sm text-green-700 font-medium">
                        <strong>✓ Validation réussie</strong>
                      </p>
                      <p className="text-xs text-green-600 mt-1">Vous pouvez maintenant choisir votre document</p>
                    </div>

                    {/* Type de document */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Choix <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.document_type}
                        onChange={(e) => setFormData({ 
                          ...formData, 
                          document_type: e.target.value, 
                          additional_info: e.target.value === 'Réclamation' ? { motif: 'Erreur dans le document' } : {},
                          numero_demande: '',
                          description: ''
                        })}
                        className={`select-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                          errors.document_type ? 'border-red-500' : 'border-gray-300'
                        }`}
                        required
                      >
                        <option value="">Sélectionnez un type</option>
                        {documentTypes.map((type) => (
                          <option key={type.value} value={type.value}>
                            {type.label}
                          </option>
                        ))}
                      </select>
                      {errors.document_type && <p className="mt-1 text-sm text-red-600">{errors.document_type}</p>}
                    </div>

                    {/* Si Réclamation */}
                    <AnimatePresence>
                      {isReclamation && (
                        <motion.div
                          initial={{ opacity: 0, height: 0 }}
                          animate={{ opacity: 1, height: 'auto' }}
                          exit={{ opacity: 0, height: 0 }}
                          className="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg space-y-4"
                        >
                          <div className="flex items-start gap-2">
                            <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
                            <p className="text-sm text-red-700 font-medium">
                              Pour faire une réclamation, vous devez avoir le numéro de demande reçu par email.
                            </p>
                          </div>
                          
                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                              Numéro d'attestation <span className="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              value={formData.numero_attestation || ''}
                              onChange={(e) => setFormData({ ...formData, numero_attestation: e.target.value.toUpperCase() })}
                              className={`input-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                                errors.numero_attestation ? 'border-red-500' : 'border-gray-300'
                              }`}
                              placeholder="ATT-2025-000001"
                              required
                            />
                            {errors.numero_attestation && <p className="mt-1 text-sm text-red-600">{errors.numero_attestation}</p>}
                            <p className="mt-1 text-xs text-gray-500">Entrez le numéro d'attestation reçu par email</p>
                          </div>

                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                              Motif de la réclamation <span className="text-gray-400">(Optionnel)</span>
                            </label>
                            <select
                              value={formData.additional_info.motif || ''}
                              onChange={(e) => setFormData({
                                ...formData,
                                additional_info: { ...formData.additional_info, motif: e.target.value }
                              })}
                              className={`select-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                                errors.motif ? 'border-red-500' : 'border-gray-300'
                              }`}
                              required
                            >
                              {motifOptions.map((motif) => (
                                <option key={motif} value={motif}>{motif}</option>
                              ))}
                            </select>
                            {errors.motif && <p className="mt-1 text-sm text-red-600">{errors.motif}</p>}
                          </div>

                          <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                              Description de la réclamation <span className="text-red-500">*</span>
                            </label>
                            <textarea
                              value={formData.description}
                              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                              rows={4}
                              className={`textarea-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                                errors.description ? 'border-red-500' : 'border-gray-300'
                              }`}
                              placeholder="Décrivez votre réclamation en détail..."
                              required
                            />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                          </div>
                        </motion.div>
                      )}
                    </AnimatePresence>

                    {/* Champs supplémentaires */}
                    {selectedDocType && selectedDocType.fields && selectedDocType.fields.length > 0 && !isReclamation && (
                      <div className="bg-gray-50 rounded-xl p-5 space-y-4">
                        <h3 className="font-semibold text-gray-900">Informations supplémentaires</h3>
                        {selectedDocType.fields.map((field) => (
                          <div key={field.name}>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                              {field.label} {field.required && <span className="text-red-500">*</span>}
                            </label>
                            {field.type === 'textarea' ? (
                              <textarea
                                value={formData.additional_info[field.name] || ''}
                                onChange={(e) => setFormData({
                                  ...formData,
                                  additional_info: { ...formData.additional_info, [field.name]: e.target.value }
                                })}
                                className={`textarea-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                                  errors[field.name] ? 'border-red-500' : 'border-gray-300'
                                }`}
                                rows={4}
                                required={field.required}
                              />
                            ) : field.type === 'select' ? (
                              <select
                                value={formData.additional_info[field.name] || ''}
                                onChange={(e) => setFormData({
                                  ...formData,
                                  additional_info: { ...formData.additional_info, [field.name]: e.target.value }
                                })}
                                className={`textarea-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                                  errors[field.name] ? 'border-red-500' : 'border-gray-300'
                                }`}
                                required={field.required}
                                disabled={field.options === 'niveaux' && loadingNiveaux}
                              >
                                <option value="">{field.placeholder || 'Sélectionnez...'}</option>
                                {field.options === 'niveaux' ? (
                                  loadingNiveaux ? (
                                    <option value="">Chargement...</option>
                                  ) : (
                                    niveaux.map((niveau) => (
                                      <option key={niveau.id} value={niveau.code}>
                                        {niveau.nom} ({niveau.code})
                                      </option>
                                    ))
                                  )
                                ) : field.options === 'annees' ? (
                                  loadingAnnees ? (
                                    <option value="">Chargement...</option>
                                  ) : (
                                    annees.map((annee) => (
                                      <option key={annee} value={annee}>
                                        {annee}
                                      </option>
                                    ))
                                  )
                                ) : (
                                  Array.isArray(field.options) && field.options.map((opt) => {
                                    // Gérer les options qui sont des objets {value, label} ou des strings
                                    if (typeof opt === 'object' && opt.value !== undefined) {
                                      return <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    } else {
                                      return <option key={opt} value={opt}>{opt}</option>
                                    }
                                  })
                                )}
                              </select>
                            ) : (
                              <input
                                type={field.type}
                                value={formData.additional_info[field.name] || ''}
                                onChange={(e) => setFormData({
                                  ...formData,
                                  additional_info: { ...formData.additional_info, [field.name]: e.target.value }
                                })}
                                className={`input-neon w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all ${
                                  errors[field.name] ? 'border-red-500' : 'border-gray-300'
                                }`}
                                placeholder={field.placeholder}
                                required={field.required}
                              />
                            )}
                            {errors[field.name] && <p className="mt-1 text-sm text-red-600">{errors[field.name]}</p>}
                          </div>
                        ))}
                      </div>
                    )}

                    {errors.submit && (
                      <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                        <AlertCircle className="w-5 h-5 text-red-600" />
                        <p className="text-red-600 text-sm">{errors.submit}</p>
                      </div>
                    )}

                    <button
                      type="submit"
                      disabled={loading}
                      className="w-full px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {loading ? 'Soumission...' : 'Soumettre la demande'}
                    </button>
                  </form>
                )}
              </div>
            )}
          </div>

          {/* Carte 2: Suivi de Demande */}
          <div className="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <Search className="w-5 h-5 text-white" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-gray-900">Suivi de Demande</h2>
                <p className="text-sm text-gray-500">Consultez l'état de votre demande</p>
              </div>
            </div>

            <form onSubmit={handleSuiviSearch} className="space-y-5">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Numéro de demande
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    name="numero_demande"
                    className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="DEM-2024-001"
                  />
                  <button
                    type="submit"
                    disabled={suiviLoading}
                    className="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50"
                  >
                    {suiviLoading ? '...' : 'Rechercher'}
                  </button>
                </div>
              </div>

              {suiviError && (
                <div className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                  <AlertCircle className="w-5 h-5 text-red-600" />
                  <p className="text-red-600 text-sm">{suiviError}</p>
                </div>
              )}

              {suiviData && (
                <motion.div
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-gradient-to-br from-blue-50 to-amber-50 rounded-xl p-6 border border-blue-200"
                >
                  <div className="space-y-6">
                    <div className="flex items-center justify-between">
                      <h3 className="text-lg font-bold text-gray-900">Demande #{suiviData.numero_demande || suiviData.id}</h3>
                      {getStatusBadge(suiviData.status)}
                    </div>
                    
                    {/* Informations de base */}
                    <div className="space-y-2 text-sm">
                      <div className="flex justify-between">
                        <span className="text-gray-600">Type :</span>
                        <span className="font-medium text-gray-900">{suiviData.document_type || 'N/A'}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Date :</span>
                        <span className="font-medium text-gray-900">
                          {suiviData.date_demande ? (
                            new Date(suiviData.date_demande).toLocaleDateString('fr-FR', {
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric'
                            })
                          ) : (
                            'Date non disponible'
                          )}
                        </span>
                      </div>
                      {suiviData.numero_attestation && (
                        <div className="flex justify-between">
                          <span className="text-gray-600">Numéro d'attestation :</span>
                          <span className="font-medium text-green-700">{suiviData.numero_attestation}</span>
                        </div>
                      )}
                    </div>

                    {/* Fil de temps avec 3 phases */}
                    <div className="mt-6">
                      <h4 className="text-sm font-semibold text-gray-700 mb-4">Progression de votre demande</h4>
                      <div className="relative">
                        {/* Ligne de connexion */}
                        <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        {/* Phase 1: En attente */}
                        <div className="relative flex items-start gap-4 mb-6">
                          <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center ${
                            ['En attente', 'Acceptée', 'Refusée', 'Traitée'].includes(suiviData.status)
                              ? 'bg-yellow-500 text-white'
                              : 'bg-gray-300 text-gray-500'
                          }`}>
                            <Clock className="w-4 h-4" />
                          </div>
                          <div className="flex-1 pt-1">
                            <p className={`font-semibold ${
                              ['En attente', 'Acceptée', 'Refusée', 'Traitée'].includes(suiviData.status)
                                ? 'text-gray-900'
                                : 'text-gray-400'
                            }`}>
                              Phase 1 : Demande créée
                            </p>
                            <p className="text-sm text-gray-600">
                              Votre demande a été reçue et est en attente de traitement
                            </p>
                            {suiviData.date_demande && (
                              <p className="text-xs text-gray-500 mt-1">
                                {new Date(suiviData.date_demande).toLocaleDateString('fr-FR')}
                              </p>
                            )}
                          </div>
                        </div>

                        {/* Phase 2: Document envoyé (2 phases au lieu de 3) */}
                        <div className="relative flex items-start gap-4">
                          <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center ${
                            suiviData.status === 'Traitée'
                              ? 'bg-green-500 text-white'
                              : suiviData.status === 'Refusée'
                              ? 'bg-red-500 text-white'
                              : 'bg-gray-300 text-gray-500'
                          }`}>
                            {suiviData.status === 'Refusée' ? (
                              <X className="w-4 h-4" />
                            ) : (
                              <CheckCircle className="w-4 h-4" />
                            )}
                          </div>
                          <div className="flex-1 pt-1">
                            <p className={`font-semibold ${
                              ['Traitée', 'Refusée'].includes(suiviData.status)
                                ? 'text-gray-900'
                                : 'text-gray-400'
                            }`}>
                              Phase 2 : {suiviData.status === 'Refusée' ? 'Demande refusée' : 'Document envoyé'}
                            </p>
                            <p className="text-sm text-gray-600">
                              {suiviData.status === 'Refusée' 
                                ? 'Votre demande a été refusée'
                                : suiviData.status === 'Traitée'
                                ? 'Votre document a été généré et envoyé par email'
                                : 'En attente de traitement par l\'administration'}
                            </p>
                            {suiviData.status === 'Refusée' && suiviData.justification_refus && (
                              <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                {suiviData.justification_refus}
                              </div>
                            )}
                            {suiviData.status === 'Traitée' && suiviData.email_sent_at && (
                              <p className="text-xs text-gray-500 mt-1">
                                Envoyé le {new Date(suiviData.email_sent_at).toLocaleDateString('fr-FR')}
                              </p>
                            )}
                            {suiviData.status === 'Traitée' && (
                              <div className="mt-2 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700 flex items-center gap-2">
                                <Mail className="w-4 h-4" />
                                Document envoyé à {suiviData.email || 'votre email'}
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </motion.div>
              )}
            </form>
          </div>
        </div>
      </motion.main>

      {/* Footer */}
      <footer className="mt-10 border-t border-gray-200 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg flex items-center justify-center shadow-sm">
                  <GraduationCap className="w-5 h-5 text-white" />
                </div>
                <div>
                  <p className="font-bold text-gray-900">{APP_NAME}</p>
                  <p className="text-xs text-gray-600">{SCHOOL_ACRONYM}</p>
                </div>
              </div>
              <p className="mt-3 text-sm text-gray-600 max-w-sm">
                Plateforme de gestion documentaire académique: demandes, suivi et communication avec l’administration.
              </p>
            </div>

            <div>
              <p className="text-sm font-semibold text-gray-900">Liens</p>
              <div className="mt-3 space-y-2">
                <button onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })} className="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                  Haut de page
                </button>
                <button onClick={scrollToForms} className="block text-sm text-gray-600 hover:text-gray-900 transition-colors">
                  Formulaires
                </button>
                <button onClick={() => navigate('/about')} className="block text-sm text-gray-600 hover:text-gray-900 transition-colors">
                  About Us
                </button>
              </div>
            </div>

            <div>
              <p className="text-sm font-semibold text-gray-900">Établissement</p>
              <p className="mt-3 text-sm text-gray-600">{SCHOOL_NAME}</p>
              <p className="mt-1 text-sm text-gray-600">{UNIVERSITY_NAME}</p>
            </div>
          </div>

          <div className="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
            <p className="text-xs text-gray-500">
              © {new Date().getFullYear()} {APP_NAME}. Tous droits réservés.
            </p>
            <p className="text-xs text-gray-500">
              Conçu pour une expérience fluide et moderne.
            </p>
          </div>
        </div>
      </footer>
    </div>
  )
}
