import { useState } from 'react'
import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import { FileText, Download, CheckCircle, Clock, XCircle, Search } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import { useAuth } from '../../context/AuthContext'
import { demandeService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function MyDocuments() {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = useState('')

  const { data: demandes, isLoading } = useQuery({
    queryKey: ['studentDemandes', user?.apogeeNumber],
    queryFn: () => demandeService.getByStudent(user?.apogeeNumber),
    enabled: !!user?.apogeeNumber,
  })

  // Filtrer les demandes acceptées avec documents disponibles
  const documents = demandes?.filter(d => 
    d.status === 'Acceptée' && d.document_path
  ) || []

  // Filtrer par recherche
  const filteredDocuments = documents.filter(doc => {
    if (!searchTerm) return true
    const search = searchTerm.toLowerCase()
    return (
      doc.document_type?.toLowerCase().includes(search) ||
      doc.id.toString().includes(search)
    )
  })

  const getStatusBadge = (status) => {
    const badges = {
      'En attente': { color: 'bg-orange-100 text-orange-700', icon: Clock },
      'Acceptée': { color: 'bg-success-100 text-success-700', icon: CheckCircle },
      'Refusée': { color: 'bg-red-100 text-red-700', icon: XCircle },
    }
    const badge = badges[status] || badges['En attente']
    const Icon = badge.icon
    return (
      <span className={`inline-flex items-center space-x-1 px-3 py-1 rounded-full text-xs font-medium ${badge.color}`}>
        <Icon className="w-3 h-3" />
        <span>{status}</span>
      </span>
    )
  }

  const handleDownload = (demande) => {
    demandeService.downloadDocument(demande.id, user?.apogeeNumber)
  }

  if (isLoading) return <LoadingPage />

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar user={user} />
      
      <main className="flex-1 ml-64 p-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-7xl mx-auto"
        >
          <div className="mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">Mes Documents</h1>
            <p className="text-gray-600">Téléchargez vos attestations et documents officiels</p>
          </div>

          {/* Barre de recherche */}
          <div className="mb-6 bg-white rounded-xl shadow-md p-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Rechercher par type de document ou ID..."
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Liste des documents */}
          {filteredDocuments.length === 0 ? (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-white rounded-2xl shadow-lg p-12 text-center"
            >
              <FileText className="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                {searchTerm ? 'Aucun document trouvé' : 'Aucun document disponible'}
              </h3>
              <p className="text-gray-600">
                {searchTerm 
                  ? 'Essayez avec d\'autres mots-clés'
                  : 'Vos documents acceptés apparaîtront ici une fois générés par l\'administration'
                }
              </p>
            </motion.div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredDocuments.map((doc, index) => (
                <motion.div
                  key={doc.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.1 }}
                  className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow"
                >
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3">
                      <div className="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                        <FileText className="w-6 h-6 text-primary-600" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900">{doc.document_type}</h3>
                        <p className="text-xs text-gray-500">ID: #{doc.id}</p>
                      </div>
                    </div>
                    {getStatusBadge(doc.status)}
                  </div>

                  <div className="mb-4 space-y-2">
                    <div className="text-sm text-gray-600">
                      <span className="font-medium">Date de demande :</span>{' '}
                      {new Date(doc.date_demande).toLocaleDateString('fr-FR', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                      })}
                    </div>
                    {doc.document_path && (
                      <div className="text-sm text-success-600 font-medium">
                        ✓ Document disponible
                      </div>
                    )}
                  </div>

                  {doc.document_path && (
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={() => handleDownload(doc)}
                      className="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                    >
                      <Download className="w-5 h-5" />
                      <span>Télécharger PDF</span>
                    </motion.button>
                  )}
                </motion.div>
              ))}
            </div>
          )}

          {/* Statistiques */}
          {documents.length > 0 && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.3 }}
              className="mt-8 bg-white rounded-2xl shadow-lg p-6"
            >
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Résumé</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="text-center p-4 bg-gray-50 rounded-xl">
                  <div className="text-2xl font-bold text-gray-900">{documents.length}</div>
                  <div className="text-sm text-gray-600">Documents disponibles</div>
                </div>
                <div className="text-center p-4 bg-gray-50 rounded-xl">
                  <div className="text-2xl font-bold text-primary-600">{filteredDocuments.length}</div>
                  <div className="text-sm text-gray-600">Résultats de recherche</div>
                </div>
                <div className="text-center p-4 bg-gray-50 rounded-xl">
                  <div className="text-2xl font-bold text-success-600">
                    {documents.filter(d => d.document_path).length}
                  </div>
                  <div className="text-sm text-gray-600">Téléchargeables</div>
                </div>
              </div>
            </motion.div>
          )}
        </motion.div>
      </main>
    </div>
  )
}

