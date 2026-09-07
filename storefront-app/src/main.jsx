import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.jsx'
import AOS from 'aos'
import 'aos/dist/aos.css'
import './ui.css'
import './styles.css'
import './motion.css'
import './product-cards.css'

AOS.init({ duration: 500, easing: 'ease-out', once: true, offset: 40 })

createRoot(document.getElementById('root')).render(
  <StrictMode><App /></StrictMode>,
)
