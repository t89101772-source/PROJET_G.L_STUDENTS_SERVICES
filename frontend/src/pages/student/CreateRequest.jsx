import { useState } from 'react'
import { motion } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { FileText, CheckCircle, AlertCircle } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import { useAuth } from '../../context/AuthContext'
import { demandeService } from '../../services/api'

const documentTypes = [
  { value: 'Attestation de scolarité', label: 'Attestation de scolarité', fields: [] },
  { 
    value: 'Attestation de réussite', 
    label: 'Attestation de réussite', 
    fields: [
      { name: 'annee_universitaire', label: 'Année universitaire', type: 'text', placeholder: 'Ex: 2024-2025', required: true },
      { name: 'niveau', label: 'Niveau', type: 'select', options: ['CPI1', 'CPI2', '1A', '2A', '3A', 'M1', 'M2'], placeholder: 'Sélectionnez le niveau', required: true }
    ]
  },
  { 
    value: 'Relevé de notes', 
    label: 'Relevé de notes', 
    fields: [
      { name: 'annee_universitaire', label: 'Année universitaire', type: 'text', placeholder: 'Ex: 2024-2025', required: true },
      { name: 'semestre', label: 'Semestre', type: 'select', options: ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'Tous'], required: true }
    ]
  },
  { 
    value: 'Convention de stage', 
    label: 'Convention de stage', 
    fields: [
      { name: 'nom_entreprise', label: 'Nom de l\'entreprise', type: 'text', placeholder: 'Nom de l\'entreprise', required: true },
      { name: 'adresse_entreprise', label: 'Adresse de l\'entreprise', type: 'text', placeholder: 'Adresse complète', required: true },
      { name: 'duree_stage', label: 'Durée du stage', type: 'text', placeholder: 'Ex: 2 mois', required: true },
      { name: 'date_debut', label: 'Date de début', type: 'date', required: true },
      { name: 'date_fin', label: 'Date de fin', type: 'date', required: true }
    ]
  },
  { value: 'Autre', label: 'Autre', fields: [
    { name: 'description', label: 'Description du document', type: 'textarea', placeholder: 'Décrivez le document demandé', required: true }
  ]}
]

export default function CreateRequest() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [formData, setFormData] = useState({ 
    email: user?.email || '',
    apogee_number: user?.apogeeNumber || '',
    cin: '',
    document_type: '',
    additional_info: {}
  })
  const [errors, setErrors] = useState({})
  const [success, setSuccess] = useState(false)

  const mutation = useMutation({
    mutationFn: (data) => demandeService.create(data),
    onSuccess: () => {
      setSuccess(true)
      queryClient.invalidateQueries(['studentDemandes'])
      queryClient.invalidateQueries(['studentStats'])
      setTimeout(() => {
        navigate('/student/dashboard')
      }, 2000)
    },
  })

  const handleSubmit = (e) => {
    e.preventDefault()
    
    // Validation
    const newErrors = {}
    if (!formData.email) newErrors.email = 'Email requis'
    if (!formData.apogee_number) newErrors.apogee_number = 'Numéro Apogée requis'
    if (!formData.cin) newErrors.cin = 'CIN requis'
    if (!formData.document_type) newErrors.document_type = 'Type de document requis'
    
    // Valider les champs supplémentaires
    const selectedDocType = documentTypes.find(dt => dt.value === formData.document_type)
    if (selectedDocType && selectedDocType.fields) {
      selectedDocType.fields.forEach(field => {
        if (field.required && !formData.additional_info[field.name]) {
          newErrors[field.name] = `${field.label} requis`
        }
      })
    }
    
    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors)
      return
    }
    
    setErrors({})
    
    mutation.mutate({
      email: formData.email,
      apogee_number: formData.apogee_number,
      cin: formData.cin,
      document_type: formData.document_type,
      additional_info: formData.additional_info
    })
  }
  
  const selectedDocType = documentTypes.find(dt => dt.value === formData.document_type)

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar user={user} />
      
      <main className="flex-1 ml-64 p-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-2xl mx-auto"
        >
          <div className="bg-white rounded-2xl shadow-lg p-8">
            <div className="mb-8">
              <h1 className="text-3xl font-bold text-gray-900 mb-2">Nouvelle Demande</h1>
              <p className="text-gray-600">Créez une demande de document administratif</p>
            </div>

            {success ? (
              <motion.div
                initial={{ scale: 0.8, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="text-center py-12"
              >
                <CheckCircle className="w-20 h-20 text-success-500 mx-auto mb-4" />
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Demande créée avec succès !</h2>
                <p className="text-gray-600">Redirection en cours...</p>
              </motion.div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Email */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Email Institutionnel <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
                      errors.email ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="prenom.nom@univ.ma"
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
                    onChange={(e) => setFormData({ ...formData, apogee_number: e.target.value })}
                    className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
                      errors.apogee_number ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="A12345"
                    required
                  />
                  {errors.apogee_number && <p className="mt-1 text-sm text-red-600">{errors.apogee_number}</p>}
                </div>

                {/* CIN */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    CIN (Carte d'identité nationale) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.cin}
                    onChange={(e) => setFormData({ ...formData, cin: e.target.value })}
                    className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
                      errors.cin ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="AB123456"
                    required
                  />
                  {errors.cin && <p className="mt-1 text-sm text-red-600">{errors.cin}</p>}
                </div>

                {/* Type de document */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Type de document <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={formData.document_type}
                    onChange={(e) => setFormData({ ...formData, document_type: e.target.value, additional_info: {} })}
                    className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
                      errors.document_type ? 'border-red-500' : 'border-gray-300'
                    }`}
                    required
                  >
                    <option value="">Sélectionnez un type</option>
                    {documentTypes.map((type) => (
                      <option key={type.value} value={type.value}>{type.label}</option>
                    ))}
                  </select>
                  {errors.document_type && <p className="mt-1 text-sm text-red-600">{errors.document_type}</p>}
                </div>

                {/* Champs supplémentaires selon le type de document */}
                {selectedDocType && selectedDocType.fields && selectedDocType.fields.length > 0 && (
                  <div className="bg-gray-50 rounded-xl p-6 space-y-4">
                    <h3 className="font-semibold text-gray-900 mb-4">Informations supplémentaires</h3>
                    
                    {/* Message d'aide pour Convention de stage */}
                    {formData.document_type === 'Convention de stage' && (
                      <div className="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                        <div className="flex">
                          <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                            </svg>
                          </div>
                          <div className="ml-3">
                            <p className="text-sm text-blue-700">
                              <strong>Important :</strong> Les conventions de stage sont uniquement disponibles pour les étudiants en <strong>2A (PFA - 4ème année)</strong> ou <strong>3A (PFE - 5ème année)</strong>.
                              <br />
                              • <strong>2A (PFA)</strong> : Stage de 2 à 3 mois (8-12 semaines)
                              <br />
                              • <strong>3A (PFE)</strong> : Stage de 4 à 6 mois (16-24 semaines)
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                    
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
                            className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
                              errors[field.name] ? 'border-red-500' : 'border-gray-300'
                            }`}
                            placeholder={field.placeholder}
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
                            className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
                              errors[field.name] ? 'border-red-500' : 'border-gray-300'
                            }`}
                            required={field.required}
                          >
                            <option value="">Sélectionnez...</option>
                            {field.options.map((opt) => (
                              <option key={opt} value={opt}>{opt}</option>
                            ))}
                          </select>
                        ) : (
                          <input
                            type={field.type}
                            value={formData.additional_info[field.name] || ''}
                            onChange={(e) => setFormData({
                              ...formData,
                              additional_info: { ...formData.additional_info, [field.name]: e.target.value }
                            })}
                            className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all ${
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

                {mutation.isError && (
                  <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center space-x-3"
                  >
                    <AlertCircle className="w-5 h-5 text-red-600" />
                    <p className="text-red-600 text-sm">
                      {mutation.error?.response?.data?.message || 'Erreur lors de la création'}
                    </p>
                  </motion.div>
                )}

                <div className="flex space-x-4">
                  <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    type="button"
                    onClick={() => navigate('/student/dashboard')}
                    className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-all"
                  >
                    Annuler
                  </motion.button>
                  <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    type="submit"
                    disabled={mutation.isPending}
                    className="flex-1 px-6 py-3 bg-gradient-to-r from-success-500 to-success-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
                  >
                    {mutation.isPending ? 'Création...' : 'Créer la demande'}
                  </motion.button>
                </div>
              </form>
            )}
          </div>
        </motion.div>
      </main>
    </div>
  )
}

