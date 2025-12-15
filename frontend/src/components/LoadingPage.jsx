import { motion } from 'framer-motion'

export default function LoadingPage() {
  // Simple loader: cercle qui “se charge” (minimal, pro)
  const size = 76
  const stroke = 7
  const r = (size - stroke) / 2
  const c = 2 * Math.PI * r

  return (
    <motion.div
      className="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.18 }}
    >
      <motion.div
        initial={{ opacity: 0, scale: 0.96 }}
        animate={{ opacity: 1, scale: 1 }}
        exit={{ opacity: 0, scale: 0.98 }}
        transition={{ duration: 0.22, ease: 'easeOut' }}
        className="flex flex-col items-center"
      >
        <div className="relative">
          {/* Glow */}
          <motion.div
            className="absolute inset-0 rounded-full blur-2xl bg-blue-300/30"
            animate={{ opacity: [0.25, 0.5, 0.25], scale: [0.95, 1.05, 0.95] }}
            transition={{ duration: 1.6, repeat: Infinity, ease: 'easeInOut' }}
          />

          {/* Ring */}
          <motion.svg
            width={size}
            height={size}
            viewBox={`0 0 ${size} ${size}`}
            className="relative"
            animate={{ rotate: 360 }}
            transition={{ duration: 1.25, repeat: Infinity, ease: 'linear' }}
          >
            <circle
              cx={size / 2}
              cy={size / 2}
              r={r}
              stroke="rgba(148,163,184,0.35)" /* slate-400 */
              strokeWidth={stroke}
              fill="transparent"
            />
            <motion.circle
              cx={size / 2}
              cy={size / 2}
              r={r}
              stroke="rgb(37, 99, 235)" /* blue-600 */
              strokeWidth={stroke}
              fill="transparent"
              strokeLinecap="round"
              strokeDasharray={c}
              animate={{ strokeDashoffset: [c * 0.92, c * 0.28, c * 0.92] }}
              transition={{ duration: 1.1, repeat: Infinity, ease: 'easeInOut' }}
            />
          </motion.svg>
        </div>

        <motion.p
          className="mt-5 text-sm font-medium text-gray-700"
          animate={{ opacity: [0.6, 1, 0.6] }}
          transition={{ duration: 1.4, repeat: Infinity, ease: 'easeInOut' }}
        >
          Chargement…
        </motion.p>
      </motion.div>
    </motion.div>
  )
}

