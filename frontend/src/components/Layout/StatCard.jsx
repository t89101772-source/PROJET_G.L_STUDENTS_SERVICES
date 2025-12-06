import { motion } from 'framer-motion'

export default function StatCard({ 
  title, 
  value, 
  icon: Icon, 
  color = 'primary',
  delay = 0 
}) {
  const colorClasses = {
    primary: 'from-primary-500 to-primary-600',
    success: 'from-success-500 to-success-600',
    orange: 'from-orange-500 to-orange-600',
    red: 'from-red-500 to-red-600',
    blue: 'from-blue-500 to-blue-600',
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay }}
      whileHover={{ y: -5, scale: 1.02 }}
      className="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all"
    >
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600 mb-1">{title}</p>
          <h3 className="text-3xl font-bold text-gray-900">{value}</h3>
        </div>
        <div className={`w-14 h-14 rounded-xl bg-gradient-to-br ${colorClasses[color]} flex items-center justify-center`}>
          <Icon className="w-7 h-7 text-white" />
        </div>
      </div>
    </motion.div>
  )
}

