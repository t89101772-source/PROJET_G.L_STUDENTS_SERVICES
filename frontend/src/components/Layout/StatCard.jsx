import { motion } from 'framer-motion'

export default function StatCard({ title, value, icon: Icon, color = 'blue', delay = 0 }) {
  const colorClasses = {
    blue: 'bg-blue-50 text-blue-700 border-blue-200',
    orange: 'bg-orange-50 text-orange-700 border-orange-200',
    primary: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    red: 'bg-red-50 text-red-700 border-red-200',
  }

  const iconBgClasses = {
    blue: 'bg-blue-100',
    orange: 'bg-orange-100',
    primary: 'bg-indigo-100',
    red: 'bg-red-100',
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay, duration: 0.5 }}
      className={`rounded-xl border-2 p-6 ${colorClasses[color] || colorClasses.blue}`}
    >
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium opacity-80 mb-1">{title}</p>
          <p className="text-3xl font-bold">{value}</p>
        </div>
        {Icon && (
          <div className={`p-3 rounded-lg ${iconBgClasses[color] || iconBgClasses.blue}`}>
            <Icon className="w-6 h-6" />
          </div>
        )}
      </div>
    </motion.div>
  )
}

