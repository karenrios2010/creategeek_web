import { useState, useEffect, useRef } from "react";

/* ─────────────────────────────────────────────────────────────
   DATA
───────────────────────────────────────────────────────────── */
const INTEGRATIONS = [
  "GoHighLevel","WhatsApp Business","Shopify","WordPress",
  "Google Ads","Meta Ads","Stripe","Zapier","Mailchimp",
  "Calendly","HubSpot","ManyChat","Twilio","OpenAI","TikTok",
];

const SERVICES = [
  {
    icon:"🌐", color:"#0095B1", bg:"rgba(0,149,177,0.12)",
    tag:"Web Dev", title:"Sitios Web de Impacto",
    desc:"Diseño moderno, ultra-rápido y optimizado para Google. Tu vitrina digital que trabaja 24/7.",
    metric:"48h", metricLabel:"time-to-launch",
  },
  {
    icon:"🤖", color:"#a855f7", bg:"rgba(168,85,247,0.12)",
    tag:"IA", title:"Chatbots Inteligentes",
    desc:"Asistentes IA entrenados en tu negocio. Responden, califican y agendan sin intervención humana.",
    metric:"80%", metricLabel:"consultas resueltas",
  },
  {
    icon:"⚙️", color:"#F1E498", bg:"rgba(241,228,152,0.12)",
    tag:"CRM · GHL", title:"Automatización de Ventas",
    desc:"Pipelines automáticos con GoHighLevel. Captura, nutre y cierra leads mientras duermes.",
    metric:"3x", metricLabel:"más conversiones",
  },
  {
    icon:"📊", color:"#4ade80", bg:"rgba(74,222,128,0.12)",
    tag:"Growth", title:"Estrategia Digital",
    desc:"WhatsApp, email y redes integradas en un ecosistema que genera clientes en automático.",
    metric:"+40%", metricLabel:"revenue promedio",
  },
];

const FEATURES_TABS = [
  {
    id:"web", label:"Desarrollo Web",
    headline:"Sitios que convierten desde el primer clic",
    sub:"Construimos con performance real: +95 en Lighthouse, SSR, Core Web Vitals optimizados. Tu sitio no solo se ve bien — posiciona y vende.",
    checks:["Diseño personalizado con tu marca","SEO técnico desde el día 1","CMS fácil de administrar","Hosting incluido · SSL · CDN global"],
    code: `// Resultado típico de nuestros sitios
const metrics = {
  lighthouse: 97,
  firstContentfulPaint: "0.8s",
  coreWebVitals: "PASS ✓",
  conversationRate: "+34%",
  googleRanking: "Top 3 en 90 días"
}`,
  },
  {
    id:"chatbot", label:"Chatbots IA",
    headline:"Tu vendedor más dedicado nunca duerme",
    sub:"Entrenado con el conocimiento de tu negocio. Responde, califica y agenda clientes en WhatsApp, Instagram y tu web — todo simultáneo.",
    checks:["Entrenado con tu catálogo y FAQs","Multi-canal: Web · WhatsApp · IG","Calificación automática de leads","Escalación inteligente a humano"],
    code: `// Flujo automático de un lead
const flow = {
  trigger: "Mensaje en WhatsApp",
  step1: "Saludo personalizado + FAQ",
  step2: "Calificación (presupuesto, urgencia)",
  step3: "Agendamiento automático → Cal.com",
  step4: "Notificación al equipo de ventas",
  resultado: "Lead calificado sin intervención"
}`,
  },
  {
    id:"crm", label:"CRM · GHL",
    headline:"Visibilidad total de cada oportunidad",
    sub:"GoHighLevel configurado a medida. Desde el primer contacto hasta el cierre, cada etapa automatizada con seguimiento multicanal.",
    checks:["Pipeline visual en tiempo real","Seguimiento automático por WhatsApp/Email","Reportes de cierre y métricas","Capacitación para tu equipo incluida"],
    code: `// Pipeline automático con GHL
pipeline.on("new_lead", async (lead) => {
  await crm.addToStage("Nuevo", lead)
  await whatsapp.sendWelcome(lead.phone)
  await email.sendSequence("nurture_5d", lead)
  await calendar.scheduleFollowup(lead, "+2d")
  // Lead nurturado sin intervención manual ✓
})`,
  },
];

const STEPS = [
  { num:"01", icon:"🎯", title:"Diagnóstico Express", desc:"30 minutos analizando tu negocio, audiencia y objetivos. Gratis, sin compromiso, con un plan concreto al final." },
  { num:"02", icon:"🏗️", title:"Construcción del Ecosistema", desc:"Diseñamos y construimos las 3 capas: web, chatbot y CRM. Todo integrado para trabajar en conjunto." },
  { num:"03", icon:"🚀", title:"Lanzamiento y Crecimiento", desc:"Lanzamos, medimos y optimizamos. Tú te encargas de tu negocio. Nosotros hacemos crecer el ecosistema." },
];

const TESTIMONIALS = [
  { name:"Carlos Mendoza", role:"Fundador · LogisticaVE", avatar:"CM", color:"#0095B1",
    text:"En 3 semanas teníamos web + chatbot operando. Las ventas subieron 40% el primer mes. Nunca pensé que era tan rápido." },
  { name:"María Hernández", role:"CEO · Clínica Dental Sonríe", avatar:"MH", color:"#a855f7",
    text:"El chatbot agenda citas solo. Recuperé 2 horas diarias que perdía en WhatsApp. Eso se paga solo en una semana." },
  { name:"Diego Romero", role:"Dir. Comercial · InmoVenezuela", avatar:"DR", color:"#4ade80",
    text:"GHL para 15 vendedores transformó nuestro proceso. Visibilidad total, seguimiento automático. Cerramos 3x más." },
  { name:"Andreina López", role:"Emprendedora · Moda Local", avatar:"AL", color:"#F1E498",
    text:"Pensé que era para grandes empresas. Create Geek me demostró que la tecnología de primer nivel es para todos." },
];

const PLANS = [
  { name:"Presencia", price:"$499", period:"pago único", color:"#F1E498",
    desc:"Tu primer paso digital: sitio web profesional que posiciona y convierte.",
    features:["Sitio web hasta 5 páginas","Diseño a medida con tu marca","SEO básico + Google Analytics","Dominio + hosting (1 año)","Formulario de contacto integrado","Soporte 30 días"],
    cta:"Empezar ahora", highlight:false },
  { name:"Conexión", price:"$299", period:"/mes", color:"#0095B1",
    desc:"Web + Chatbot IA + CRM automatizado. Tu ecosistema completo en piloto.",
    features:["Todo de Presencia incluido","Chatbot IA entrenado en tu negocio","WhatsApp Business integrado","CRM GoHighLevel completo","Automatizaciones de seguimiento","Dashboard de métricas · Soporte prio.","Sesiones mensuales de optimización"],
    cta:"Plan más popular →", highlight:true },
  { name:"Ecosistema", price:"Custom", period:"a medida", color:"#a855f7",
    desc:"Equipo tecnológico externo completo para empresas que quieren escalar sin límites.",
    features:["Todo de Conexión incluido","Múltiples chatbots y flujos","Automatizaciones sin límite","Integraciones con sistemas actuales","Capacitación para tu equipo","Account Manager dedicado","SLA 24h garantizado"],
    cta:"Hablemos →", highlight:false },
];

/* ─────────────────────────────────────────────────────────────
   UTILS / ATOMS
───────────────────────────────────────────────────────────── */
const GF = `@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=DM+Mono:wght@400;500&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');`;

const LOGO_SRC = "data:image/png;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAFRAkUDASIAAhEBAxEB/8QAHQABAAICAwEBAAAAAAAAAAAAAAYHBQgCAwQJAf/EAFIQAAEDAgMEBQUKCwYFAwUAAAABAgMEBQYHERIhMUETUWFxgRQikaGxCBcjMjZCUlV0sxUWNGJzkpOywcLRU1RygqLSJDM3Y4OUpOElQ2bj8f/EABsBAQACAwEBAAAAAAAAAAAAAAAFBgMEBwIB/8QAPhEAAgEDAQUECAMGBgMBAAAAAAECAwQRBQYSITFBUWFx0RMigZGhscHhFDTwFRYjMjVCM1JyorLxU2KC4v/aAAwDAQACEQMRAD8A0yAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMhZLNdL1U+T2yilqXp8ZWpo1veq7k8SQ5c4JnxLOtVVK+C2RO0e9PjSr9Fv8VLytdvorXRMo7fTR08DE3MYnrXrXtUlbLTJXC358I/FlW1raanYSdGkt6fwXj39xV1myine1r7vdWRLzip2bS/rLp7FJHTZXYWiREkZWVHbJPp+6iE4BOU9OtoLhHPjxKPX2h1Gs8uq14cPkQyXLLCb26NpaiNettQ7X16mFumUVA9rnWy61ELuKNnaj07tU0VPWWaD1OwtprDgvkeKWvajSeVWb8ePzya6YnwZfsPostZS9LTIv5RAu0zx5t8UQjptY9rXsVj2o5rk0VFTVFQqTNPAdPR0019szWwxM86opuDWoq/GZ1d3o6iGvdKdKLnSeV2Fx0bapXM1QuliT5Ncn49ny8CrgAQpcwAAAAAAAAAAAAAAAAAAAAAAAAAAACY5OfL6j/Ry/uKX4Sllpv4qnv72OOOX3KvrO0n7MuFR9HvZWc5x29z7DVIGwWbn/Ty5/wDi++Ya+mve2n4WooZzwySOi6r+06Drbm7h4xnPRPsXaAAaZLgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAyuFLNNf79TWyHVvSu1kenzGJvc70evQxRbeQdsalPcLw9urnPSnjXqRERzvTq30G1ZUPT1owfLqRes3zsbOdZc+S8Xw+5ZVuo6a30MNFSRJFBCxGManJE/iegEVzPxA/D+GXyU7tmrqXdDAqLvaqpvd4J61QuFScaNNyfJHIbehUvK8aceMpP9MxuPMxaSySyW+2RsrK9u56qvwcS9S6fGXsT08irrrjTE9yerp7xUxtX5kDuianZo3TXxMA5Vc5XOVVVV1VV4qfhUri/rV5c8LsR1jTtBs7KCSipS6trL+3sMnDiG/QyI+O9XFrk5pUv/qS/C+aN2opWRXpqXCm4K9Go2VqdaaaI7x9JXoMVK6rUnmMmbd1pdpdQ3atNP2cffzNmWYgs77Ct8Suj8gRm0svV2acdrXdpx1KTzBxpV4mqlgi2oLbG7WKHm/85/b2cvWRbppvJ/J+lk6Ha2+j2l2drTTXThrpzOs2rvU6lxBQ5Lr3kVpWzVvYVXVb3n0z0Xn3k2wHgL8abPLcPwr5H0dQsOx5P0mujWrrrtJ9L1Eh953/APIv/Zf/ALDK5DfJCr+3v+7jLBJS00+3qUIylHi12vzKxq+0Go297UpU6mIp8OEfI11xbhSosuI4rJSSyXKaWJr2bEOy5VVV3bOq9RI7HlPdamNst0roaBFTXo2N6V6di70RPBVLdZb6RlzkuSQtWrkjbGsqpqqNTXRE6k3qeo9U9IoqblLl0X64mOvtdeSpRhS4PHGWFlvuXJe4pfHeX9BhzDbrlDX1M8zZWs0ejUbovdv9ZXRe+dXyGk+0R+0o2kp56uqipaaJ0s0rkYxjeLlXghEanRhSrqFNY4Ft2ava11ZOrXll5fF9mEcI2PkkbHGxz3uXRrWpqqr1IhOLDlhiC4MbLWrFbYl3okvnSfqpw8VRSxsAYKosN0rJ5mx1Fzenwk2mqM1+azqTt4r6iWkha6PHG9W59hA6ptfPfdOzXBf3Pr4LzK0psoba1iJUXirkdpvWONrE9C6nCrygonNXyS81EbuXSxI9PUqFlyyxQs25ZGRt63OREP1j2PbtMc1zetF1Q3/2da8tz5kAtotUT3vSv3LyKDxHl3iKzxvnZCyvp271fT6qqJ1q3j6NSIG1pXOaOBYK+lmvNngSOujRXzRMTRJ05qifS9veRl5pG7Fzo+7yLLo+1rqzVG8SWeUl9fMpg9tptVxu1R5PbaKaqk5pG3VE714J4niNhMra231uD6V1BTw07ok6KojjbppIib1Xr13Lr2kfY2sbmpuuWCwa5qk9Nt1VhDey8dy8Sv7TlNeqhrX3CtpaJF4tTWV6eCaJ6zPU+UNtanw94q5F0+ZG1vt1LLBYYaXbRX8ufE59W2o1Kq8qe74Jfd/ErWbKG1qnwN2rGL+exrvZoR69ZU3qkjdJbqqnuDW/M06N69yKqp6y6wJ6XbTX8uPAUNqNSpPLnvLsaX/fxNWKymqKOpfTVcEkE0a6PjkarXIvcp78MWKtxFcloKBYUmSNZPhXK1NEVE6l6y8swMJ0uJbW/ZYxlwiaq082m/X6Dl+ivq499bZJxvixzNFI1WPZSyNc1eKKjm6oQtTTnSuI05cYyZc6G0KutPq16axOC4rn7fAzuX2Ar5YsUU9yrX0awRsejujkVXb2qibtO0tIAsdvbQt4bkORzrUNRrahVVWtjOMcP13mDx7aqq94TrbXRrGk83R7CyO0b5sjXLqvcilVe9Vif+0t/wC2d/tLxBiubClcS3p5ybena7dafSdKjjDeeK8F9DW7FmF7lhmSnZcXU6rUI5WdE9XcNNddUTrMGWj7oD8stH6OX2tKwiY+WRsUbHPe9Ua1rU1VVXgiFXvKMaNeVOPJeR07R7upd2UK9Xm8597R+NRXKjWoqqu5ETmTPD2W2Iroxs1RGy3QO3otRrtqnYxN/p0LCy5wLS2Kmjr7hE2a6PTa87RWwdje3rX0ds3JW00dNb1b3eZVtW2vlGbpWaXD+5/RfV+4rKlygoGtTyq81Mjv+3E1ievU/arKG3uavk14qo3abukja9PVoWVJIyNu1I9rG9bl0Q/IpI5WbcUjHt62rqhI/s615bnzK7+8WqZ3vSv3LyKKxDlriG1xump2MuMLd6rT67aJ/gXf6NSFuRWqrXIqKm5UXkbWEGzKwNBfKaS422JsV0YiuVGpolQnUv53UvgvZHXekJRcqPu8ixaTtdKc1SvEuP8AcuHvX1RRYP17XMerHtVrmroqKmiop+EAX0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAF+ZNwpFgKkeiadLJK9e3z1b/KUGXzkxOkuBKeNF1WGaRi9nnbX8xLaNj8Q/DyKntln8Asf5l8mTQp7P2oe672ylVfMZA6RO9ztF/dQuEqHP6ke24Wyu0VWPifEvYrV1/m9RL6rn8LLHd8ypbKuK1OGex49zKwABUjrIAAAAABdmQ3yQq/t7/u4ywSvshvkhV/b3/dxlglzsPy0PA43r/9RreJ5btcKS126avrpUip4W7T3L7E61XhoVFfs17tPUObZ6eGjp0XzXSN25F7V5J3aL3mUz8uMjILba2OVGSK6aROvTRG+13qKlInU7+pGo6VN4wWvZrQbapbK5uI7zlyT5JLhyJBe8Y4hvVvdQXKtbPA56P06FjVRU4b2ohL8ibIyerqr7MxHJTr0MGqcHqmrl70RUT/ADFYF+5OwMiwDRSNTfM+V7u/bc32NQ19NUq9ypVHnCzx/Xeb+0koWGmuFCKipNLgsc+L5dqWCYEIzTxjJhykiorerfwjUt2kc5NUiZw2tOaqu5O5SbkVxJgSy3+6OuNfJWdM5rW6RyojURE5JoWG6VWVNqjzZz/Sp2lO5U7tZgunPL6ewoSvrauvqHVFbUzVMzuL5Hq5fWdtou1xtFUlTbayamkRfmO3O7FTgqdily+9Vhj+0uH7Zv8AtHvVYY/tLh+2b/tK+tKuk95NZ8S/ParS3Dcae72bvD3GXy9xOzE9lWdzGxVcCoyojau7XTc5Oxd/oUkhHcKYPtWGqmae3SVSumYjHpLIjk0RdUXcib/6kiLFbqoqaVX+Y55qDtncSdrncfLPTuNfM07MyzYwqGQt2aepRKiJE4JtKuqfrIvhofuW+LEwvcah1QySWkniVHxs01203tVNfFPHsJX7oCButnqU02l6Vi93mqn8SvcO2C63+qWntlK6VW/Heu5jE7VXh7SsXEZ0Lxqlzzw9p02wqUb7R4u7fqtYbbxyeM59mTP4hzIxHc5HtpZ/wbTrwZB8fTtfx17tCKz3CvqH9JPXVMr1+c+Vzl9alnWvKFNhHXS8Ltc2U0e5P8zv6GXiynw2z41Tc5O+ViexpmlY31b1p/FmnT1zRLJblFcuyP1fMq2xYsv9mqGS0lxncxq74ZXq+NydStX2popf2FrxDfrFTXSBuwkzfOYq/Eci6OT0oRf3qsMf2lw/bN/2knwzY6LD1t/B9A6Z0PSLJ8K7aXVdNeSbtxI6fbXNCTVR+r4ld2g1HTb6mpUItVE+eMZXeZQo3MSatwzmJWVlpnWlkqY0l2moi7nfG4pzc1VLyKTz5+V9J9gZ95IetX4UFJc00Y9kmnfOnJZjKLTT5dGevLHFmIbrjCmorhc5J6d7JFcxWNRF0aqpwQuEoPJz5fUf6OX9xS/BpE5ToNyeePkNraFKjexjTiordXJY6sjuZFfWWvBdfXUE6wVMXR7D0RFVNZGovHsVSmvx9xd9dS/s2f7S3M3P+nlz/wDF98w19NHV61SFdKMmuHb3sndkbO3r2UpVaak9580n0j2mSvl9u17fE+61j6l0KKkauaibOvHgidSEvyRsjK+/y3Sdm1FQNRY0VNyyO10XwRFXv0K+LwyNp2xYOkm0Tamq3uVexEaiJ6l9Jq6dB17pOfHHElNoqqstMlGit3PBY4c+fwyT0ieZWLEwxamJTta+vqdWwNdvRiJxevdqm7mq95LCNYpwXaMSV8dZcZKvpI40ja2ORGtRNVXhou/eWW5VV02qX8xzXTZW0bmMrr+Rc0uvYUFdLlX3SpWouFZNUyquusjtdO5OCJ2IcbbcK621KVFBVzU0qfOjeqa9/WnYXT71WGP7S4ftm/7R71WGP7S4ftm/7Svfsq6zvZWfE6D+9Wl7m5h7vZu8PcezLLFq4ltr4qtGtuFNokuymiSNXg9E5dqf1JeRjDGCLPh24rX26Ws6V0axuSSRFarVVF4aJzRCTlhtVVVNKrzOf6nK1ncyla8IPp2dpRec9mZbMU+WQsRsNezpdE4dIm5/p3L4kGLkz8gR1it1TpvjqljReraaq/ylNlW1KmqdzJLx951DZy5lcadTlLmuHu4L4AAGiTgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAALTyFurWS19lkdor9KiJFXiqbneOmz6FKsPbYrlUWe701ypV+Fp3o5EXg5OaL2KmqeJs2lf0FaMyO1ax/HWk6HV8vFcUbQEfx/YExHhuahZolSxelp1Xhtproi9ioqp4mRw/dqO92mG5UL9qKVN6c2O5tXtQ95cZRhWp4fFM47TnVs66kuEov4o1WqYJqaokp543RyxuVr2OTRWqnFFOs2Expge1Yl+HfrSVyJolRGmu11I5Pnepe0rG6ZY4opHr5NDBXR8nRSo1dO52nq1KtcaZWpP1Vldx1HTtpbK7gvSSUJdU+C9j5fUhIJTDl9i+WRGfghzO180aIn+omGF8qEZKyoxBVNkRN/k0CrovY538E9JipWFxUeFFrx4G3da7YW0N6VVPuTy/h9StG2i5Os63htHKtC2To1m083X+nLXhruPCbTMpKVlElEynibTIzo+hRibGzw004aFM5lYBksyyXW0MdJbuMkeuroP6t7eXPrNq70qdGCnB57f12EVpO1NK9rOlVW42/V7+59/z+csyG+SFX9vf93GWCV9kN8kKv7e/7uMsEnbD8tDwKPr/APUa3iUzn38o6D7J/O4rgsbPv5R0H2P+dxXJWdR/MzOl7Pf02j4fVgvzJudk2AqONumsEksbu/bV3schQZZWRt9ZS3GosdQ/ZZV/CQa8OkRN6eKafqmXSqqp3Cz14GrtVayuNPbjzi1L3ZT+DyXGQ/GeO4MMXRlDU2yom24kkZIx6I1yKqpz5oqEwMBjfC9Hii1pTTu6GoiVXQTomqsVeKL1ou7VOwstyqrpv0T9Y5rp0rVXEfxazB8+fDv4ES9962/U9X+0aPfetv1PV/tGkEvOBMT2yZzHWyWrjThJStWRHJ16JvTxQx1PhzEFRJsQ2S4uX7M9ETvXTRCvyvr6Lw+fgdAp6HodSG/Fpr/V9yzPfetv1PV/tGj33rb9T1f7Rpi8G5XVc07KrEekECaL5Kx+r39jlT4qdy69xHsxcHz4ZuHSwo+W2TO+BlXerV47Du3q608TNO41CFP0kuXgjUo6fs/Xufw1PjLxeH3J54s92N8TNxzW2i3UFHLTvSZWJ0jkXac9WonDq0UuPD1oo7HaYLdRMRscTd7tN73c3L2qUDl25jcb2hXqiJ5S1N/Wu5PWbHGzpUnWc60+MnwIzaqCs40rOjwppN4723+vacZpI4YnyyvbHGxque5y6I1E4qq9RAbnmvYaad0VJTVdYjV06RqIxq92u/1ISfG9DU3LCdyoqPVZ5YV2ET5ypv2fHTTxNbZY3xSOjlY5j2qqOa5NFRepUPup3tW3ajDr1PmzOjWmoQnOu8tPGM49pb/vvW36nq/2jSZYNxBDiW0LcYKeSnYkro9l6oq6oiLru7zXKipKmuqo6WjgknnkXRjGN1VTYfL6xPw9hiCgncjqhyrLNouqI93JO5ERPAx6bd3FxUe//Ku4zbSaVp1hQXoVibfa3w6kgKTz5+V9J9gZ95IXYUnnz8r6T7Az7yQ2NX/LPxRobI/1FeDMfk58vqP9HL+4pfhr7lNUMp8fW5Xro2RXx69qscievQ2CMeiv+A/H6Iz7Zpq+i/8A1XzZFM3P+nlz/wDF98w19Nl8Y2l18wzXWtj0Y+dibCrw2mqjm69mqIa/1OGcQ09U6mkstf0qLpoyBzkXuVE0XwNPWaU3VjJLhjHxZMbHXVGFrOlKSUt7PHswvIxBd+RdS2XCE0GqbUFW5FTsVrVRfb6Cpb5h+7WSGllulI6nSpRyxtcqKu7TXXTgu9NxJ8l76y2Yjfb6h6NguCIxFVdySJrs+nVU71Q1dPm6FylPhnh7yU2gpK+0ycqL3scVjjy5/UvEiuN8Zw4WqqeKpt09QyoYrmSMeiJqi6Km/q3eklRh8W4fosSWh1BWasVF2opWp50butOvtTmWe4VR036J4kcysJW8a8fxKzDr5+whfvvW36nq/wBo0e+9bfqer/aNITfcv8TWuZyMoH10KL5stKm3qn+FPOT0GHiw9f5ZNiOyXJzupKZ/9CvSvr6DxLn4HQaWiaHVhvwaa/1PzLO9962/U9X+0aPfetv1PV/tGmDwlldcquZk9+XyKmTesLXIsr+zduanr7DwZmYKfh6pWvoGufa5XaJxVYHfRVerqXwXtzSuNQjS9K+XgsmpTsNn6lyraHGT73jwznmdmYuOabFFqp6Knop6fop+lcr3oqL5qpwTvIKAQ9atOtPfm+Jb7Oyo2VJUqKxEAAxG0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAASLA+LK7C9cr4fhqSVU6encu53anU7tL1w3iG1YgpEqLbUteqJ58Tt0kfen8eBrQdtJU1FJUMqKWeSCZi6tfG5WuTuVCRs9Rnbeq+MSu6xs7Q1F+ki92fb2+K+ptQCjbLmliGia2OtbT3CNOcjdh/6zd3pRSSU2b9C5E8ps1TGvPo5Wv9qITlPVbaa4vHiUivstqVJ4UN5dqa+uH8CzgVtNm7akb8Faa169TnNantUwt0zcucrVbbrZTUuu7alesrk7U4J7T1PVLaK/myeKWzOpVHj0ePFrzyW5X1lLQUr6qsqI6eBiaufI7REKXzJx6++bVstTnxW1F8967nT96cm9nPn1ESvd7ut6qOnudbLUuT4qOXRre5qbk8DHENeapKutyCwviy46NsvSspKtXe9Ncuxeb/WC7MhvkhV/b3/dxlglA4Kx3V4YtUtBBQQVDZJ1mVz3qioqtamm7/CZ333rl9T0n7Rxv2mpW9OjGEnxXcQWrbO39ze1KtOKcW+HFHTn38o6D7H/ADuK5M/jbE0+KK+GsqKWOndFF0aNY5VRU1Vdd/eYAg7ypGrXlOPJl20i2qW1lTpVFiSXH3g5wSyQTMmhe6OSNyOY5q6K1U4KhwBrEi1ngy9MvsfUd7hjoblIymuaIjfOXRk/a3qXs9HZOTVIlVgx/iW0MbEysSrgbwjqk20TuX43r0J611jdW7WXt8yi6psfvzdSzaWf7X9H9H7zYMFT0ucD0aiVVia53N0dTonoVq+07ZM4YUb8HYJFd+dVIifukitUtcfzfB+RXXsxqiePRfGPmWmRzMC7WG3WKaG9tZUNnYqMpUXz5F5adWi/O5d5Wl3zVv8AVsdHRQ01A1fnNbtvTxXd6iDVtVU1tS6prKiWomfvc+RyucvippXWsU91xpLPjyJrTNkK/pFUuZbqXHCfH39PYcYJpKepjqIHOjkiej43Iu9qouqKbG4LxHR4ks7KuB7UnaiNqIdd8b/6LyX/AOTW49dquVfaqxtZbqqSmnbwcxeKdSpwVOxSLsb12snwynzLNrmix1OkkniceT+jNojH3Gx2a4ydLX2ujqZPpyQtV3p01KrtWbdzhYjLjbKerVPnxvWJV79yp7DJOzgg2V2bDKq8kWpRP5Sf/aVpOPrP3oon7t6tQn/Dh7VJeaZY9utlttrVbb6ClpUXj0MTWa9+ibznBW0s9bUUcMrXzUyN6ZqfM2tdEXt0Th3FL37NK/V8ToaGKG2sduVzNXyfrLuTwTUkOQb3yU95kke573SxK5zl1VV0dvVTzR1GlUrRpUlw+x6u9nrm3tJ3V1LisYWcvi0uLLPKTz5+V9J9gZ95IXYUnnz8r6T7Az7yQav+Wfij7sj/AFFeDIJQ1M1FWQ1dO7ZmgkbIx3U5F1Q2RwnfqPENnir6R6bSoiTR674382r/AA60NaD32O8XKyViVdsq5KeXg7Te1ydTkXcqd5B2F87WTyspl217RFqdNbrxOPJ9PBmzwKftub1dGxG3C0QVComivhlWPx0VF/ge1+cEKNXYsMiu5ItUiJ+6T61S1azvfBlDnsvqcZYVPPtXmSXNq0fhXB1Q+Nus9GvlDO5PjJ+qqr4IUC1VaqOaqoqb0VORNcR5lX+6wSU0CQ2+nkRWuSJFV6ovJXL/AARCEkDqVxSr1VKmXvZywurG2dK4xzylzwXRlzmFT3CCK2XydsNc1NmOd66Nm6tV5O9vqLFNUiSYexviOyRthpq3pqdvCGoTbanYnNE7EVDctNXcFu1lnvIfVdkVVm6to0s/2vl7OzwNiQVLRZwTo1ErLHG9ebop1b6lRfaeh+cECNXYsMiu5ItUiJ+6Sa1S1a/m+DK3LZjVE8ei+MfMtIw2MLrZrXZpnXp0boJWKzoF3um3fFROf8CrbtmvfKlqsoKWloUX52iyPTxXd6iDXK4VtyqnVVfVS1MzuL5Harp1J1J2Gpc6xTUWqSy/gSunbH3EpqdzLdS6J5fv5Lx4nVUOidPI6GNY4lcqsYrtpWt13Jrz7zrAK2dGSwsAAA+gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHfQUlTX1cdJRwPnnlXZYxiaqqlt4Qyto6djKrEL/KplTXyZjlSNneqb3L6E7zZtrSrcvEF7ehGalq1tp0c1nxfJLm/13lSUdJV1svQ0dLPUyfQijV6+hDO0uBMW1CIsdknbr/aObH+8qGwVFR0lDAkFHTQ08ScGRMRqehDvJmnokMevJ+wp1fbWs3/BpJLvy/lg18fl5jFjdpbM5U7KiJfY4xNxw9fbc1X1tprYWJxe6FdlPHgbMg9y0Sk16sn8PsY6e2t0n/Epxa7sr6s1SBsPibA+H74xzpKRtLUrwnp0Rrte1ODvEpnGeErnhiqRKlOmpHrpFUsTzXdi9S9no1Im606rbrefFdpatL2htdQe4vVn2P6Pr8+4jwANAngAAAAAAAAAAAAAAAAAAAAAW57n/wDI7v8ApIvY4qMtz3P/AOR3f9JF7HEjpX5qPt+RXtqf6XU9n/JFolJ58/K+k+wM+8kLsKTz5+V9J9gZ95ITWr/ln4opeyP9RXgyvgAVQ6qAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADlEx8sjYo2Oe96o1rWpqqqvBEOJP8AJOyNuGIpLnOxHQ0DUczXnI74voRFXv0M1vRdaoqa6mnf3kbK3nXl/av+l7ywcuMIQYbtrZp2Nfc52/DScdhPoN7E59a+BLQeO9XKltFrqLjWP2YYGbTtOK9SJ2quiFzp04UKe6uCRxqvXrX1dzn60pP/AKS+h211XS0NK+qrKiKngYnnPkcjUT0kKumaeHKWRY6VlXXKnzo2I1npcqL6iqcX4luOJbi6pq3qyFqr0NO1fMjT+K9a/wD8MIQVxrM3LFJYXaXrT9jqMYKV225di4Je3qXHHm9a1eiSWmta3mrXtVfRuJPh3G2Hb5I2GlreiqHcIJ02Hr3clXsRVNdQYKesV4v1sNG7cbIWFSOKeYvxz8H9ja0g+auJ7TbbRNaZ4Yq6rqWaeTuXdGnJ7tN6dac+7iQOy5l3q32Ca3SIlTUI1G01TIuro056/S05a+OpC6uonq6mSpqZXyzSuVz3vXVXKvNTbutXjKlu0lxfPPQitK2TqU7nfuX6sXww+ff3L4nUACvl/ByjY+R6MjY57l4NamqqWXgbLKSsijr8QrJBC5NplK1dHuT85fm93HuLTtNptlqhSK3UMFK3TRejYiKvevFfElrbSatVb03ur4lV1Hay1tZOnSW/Jexe/r7Pea5x4dxBK3ajsVze3rbSSKnsPLWW64UX5ZQ1VN+licz2obRn49rXtVr2o5qpoqKmqKbj0OOOE/gQ0dtqufWpLHj9jVMF94qy7sV4jfJSwtt1Wu9JIG6MVfzmcPRopS2IrLX2G5vt9wi2JG72uTe2RvJzV5oRV1Y1bbjLiu0tWla5baksU+El0fP2dpjQAaRMg/Wtc5yNa1XOXciImqqWDgLLie7wx3K8PkpaJ/nRxN3SSp1/mt9a+stqzWO0WaJI7Zb4KdNNFc1ur173LvXxUlLbSqtZb0vVRV9T2qtbObp01vyXZwS9vkjXaHD9+mZtw2S5yN620r1T2HRW2u50SKtZbqumROcsLme1DaE/FRFRUVEVF4opvPQ4Y4T+BCR22q540ljx+xqmDYLE+ArBe2PelM2hql4T07Ubv/Obwd7e0pbFmHLjhu4+SVzEVrtVimb8SRvWnb1pyIu70+rbcXxXaWfStftdR9WPqz7H9O0wxbnuf/yO7/pIvY4qMtz3P/5Hd/0kXsce9K/NR9vyMW1P9Lqez/ki0Sk8+flfSfYGfeSF2FJ58/K+k+wM+8kJrV/yz8UUvZH+orwZXwBKsA4NrcTVfSO2qe3Ru+FnVPjfms619SepaxSpTqyUILLOnXN1StaTq1XiKIxHDLImscT3onNrVU5+TVP93l/UU2ctFuo7Tb4qCggbDBEmjWpz61Vear1nomkjhifNK9scbGq5znLojUTiqqTa0Th60/h9ykz229ZqFHK6cePyNWnU87Wq50MjUTiqtU6id5mY5kvsrrZbHujtjHec7gtQqc1/N6k8V7IIQ1eEITcYSyu0uVjWrVqKqVobjfTOffwXHuBm7LhDFl7on11mwve7lSs1256Sglmjbpx1c1qonBS1/czZGVeYdfHiDEEctLhank7Wvrnou9jF5MRdznJ3Jv1Vu9lvo6S3UEFBQU0VLS08aRwwxMRrI2ImiNRE4IiEHe6pG3luQWX1JKnQcllnyjljfFK6KVjmSMcrXNcmitVOKKnJTibUe75wraKKssOLKOCKnuFe+WmrNhNFn2GtVj1TmqJqir1K1OSGq5vWtdXFJVEsZMU47ksAAGweQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAXpkjSNp8FpUaedU1D3qvYmjU/dUos2ByiVFy9tiIqLp0qL2fCvJbRkncPw8ip7YzcbBJdZL5N/QlhVmflye2C3WhjtGyK6eVNeOm5vhvd6C0yls+mvTFNE9VXYWiaid6Pfr7UJfVZONtLHXBUdlqUampQ3umX8CuwAVI60ADIYapoazEdspKlm3BPWRRyN1VNprnoipqm9NynqMd5pI8VJqnBzfTiY8GwXvdYN+p/8A3M3+8w2NcD4Wt2FLjXUdr6KohhV0b/KJF0XVOSu0JOej14Rcm1w8fIrFHa+yrVI04xlltLkuv/0UsWNkvhiO41z75XRo+npHo2Brk3Pl46/5d3iqdRXJsjgO3ttmD7ZStbo7oGyP/wAT/OX1qedKt1VrZlyXEybVX8rWz3IPDm8ezr5e0zhhsV4lteG6JKi4Srtv1SKFiavkXsTq7V3GZKOxzY8XX3E9ZXLZ6t8KPWOn3JokbV0bpv58e9VJ++uJ0aeacctlC0SwoXlfFxNRguL4pZ7lkydVm/WrL/wtmp2R68JJVcqp4ImhnMLZo2241LKS60y26V66Nl29qJV7V0RW+tO1CtfxKxX9R1foT+o/ErFf1HV+hP6kJC8voyy037PsXato+hVKe5GUYvtUuPxZsYR3MDDkOJLDJTo1qVcSK+mkXijurXqXgvgvI68tPwuzC0NLeqaaCppnLE3peL400Vq+GungSYsKUbil6y4NcjnzdSwus05ZcHwa5P7M1Tc1zXK1zVa5F0VFTRUUm+UeGGXy8Pra2PboaJUVWrwkkXg3tROK+HWYzM+hbQY5uUTE0ZJIkzf86I5fWqlu5UW9tBgeh0RNupRah69auXd/pRpW7C0Urpwlyj9Do2u6s6elxq0uDqYx3ZWX8OBKzGYkv1tw/QLWXKfYaq6MY1NXyL1NTn7DJlN5m2jFV+xRNJDaaqSjp06Kn0TzVTm7jzXXw0LBeV50aeYLLKDo9jSvLncrTUYri+KXsWT0V+b1SsqpQ2eFsaLuWaVXKqdyaaesyeHc16CrqG094olodpdEmY/bYnemmqJ6SvPxKxX9R1foT+o/ErFf1HV+hP6kDG8v1LOG/Z9i9VNH0KdPcTiu9S4/M2KjeySNskbmvY5EVrmrqiovBUUxWL7FTYisc1uqERHKm1DJpvjenB38F7FUwmUzL3S2B9tvVHPAtM/SBZE4xry8F18FQmRYoNV6SclzXI57XhKwumqc8uL4NfBmq9XTzUlVNS1DFZNC9Y5Gryci6Kha/uf/AMju/wCki9jiL5zULaPG8sjGo1tXCyfROve1fW3XxJR7n/8AI7v+ki9jiu2NL0V9udmfkzoet3KutDdZf3KL+KLRKTz5+V9J9gZ95IXYRDEeDIcQYyp7pcHotDT0rI+hRd8r0e9dF6m6KneTWoUJ16O5DnlFL2fvaVld+mqvgk/+it8usC1GIJW19ej4LW1eOmjp9OTezrX0dl40VLT0VJFSUkLIYIm7LGMTRGodkUccMTIoo2xxsRGtY1NEaicEROSHJVRE1Xch7tLOFtHC59WYtW1itqdXenwiuS7PN95xkeyON0kjmsY1FVznLoiInFVUpPNDHC3uR1qtb3Nt0bvPkTcs6p/L1Jz4n7mdjt95kfabTI5luaukkiblqFT+Xs5lfkPqWo7+aVJ8Or7S37ObO+gxdXK9bouzvff8vHkLy9zNkZV5h18eIMQRy0uFqeTta+uei72MXkxF3Ocncm/VW/nuZcjqrMSvZiC/slpsLU0mi6KrX1z2rvjYvJiLuc7wTfqrd7bfR0luoIKCgpoqWlp40jhhiYjWRsRNEaiJwREKVqWpeizSpP1ur7PuX2jR3vWlyFvo6S3UEFBQU0VLS08aRwwxMRrI2ImiNRE4IiHjxTf7Rhiw1d9vtbFRW+kZtyyvXh1IicVVV3Iib1VdEP3FF9tOGbBWX2+VkdHb6OPpJpn8ETgiInFVVVRERN6qqIh8/fdAZwXfNG/aJ0tFh+kevkNDtceXSyableqeDUXROarD2VlO6n3dWbFSooI6PdBZq1uaeLWVqwOpLRQo6K3Url1c1qrve/ltu0TXTciIib9NVwmDsscfYvoHXDDmFrhXUbdf+IRiMjcqcUa5yojlTqTUj+HKSnr8Q22hq5ehp6iriilk1+Ixz0RV8EVT6m2uho7Xbaa3W+njpqSlibFBDGmjWMamiInchOXl0rCEYU4mtTh6Vttnyzv9lu+H7pLa75baq3VsXx4KmJWPRF4LovJeS8FMefRzPnKSzZo4d6GbYo73StXyCvRu9i8ejfpvdGq8uXFOaL8+sYYbvOEcRVdgv9E+jr6V2y9juCpyc1eDmqm9FTiZ7G+hdR7JLoealJwfcYgAG+YgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAXZkXXNnwtPRK7z6WpXd1NciKnr2ikyX5T39tkxQxlQ9G0lYiQyqq7mrr5rvBd3cqm9p1ZUbhN8nwIPaKzld2E4xXFcV7Ptkv4rnPSzvq7LTXaFiudRvVsun0Hab/BUT0ljHXUQxVFPJTzxtkikarHscmqORdyoparmiq9J031OW6deysrmFePR/Dr8DVYE3x5gC4WSeSrt0clXbVVVRWpq+FOpydX53p0IQUytRnRluzWGdktLyjeUlVoyyn+uPYDLYN+V9m+3wfeNMSZnBEUsuMLOkUb5FbWwvcjWqujUeiqq9iHyj/iR8Uert4t5t9j+RsoR/Mf5DXb7OvtQkBH8x/kNdvs6+1C63H+FLwfyOL6f+bpf6o/NGuRtNblYtvplj02FiardE03aIasmxeXFybdMGW6ZHIr4okgkTmjmebv70RF8SB0SaU5R7UXrbalKVClUXJNr3/wDRIgCjs2rDcLRfZrlA6dbfWPWRHNcukb13uavVv1VOxewmbu4lbw31HJTtJ06GoV/Qyqbj6cM57uaLxBqx5TU/3iX9dR5TU/3iX9dSL/bi/wAnx+xZ/wByJf8Am/2/c2nBrTZrbfrw2d1tiqqhsDFfIrXLoiJy113r2cVMd5TU/wB4m/XU9PWmkm6fB9/2PEdjFKTirhZXPhy/3EyzsVi43dsoqKlNHtd+/wDhoW/gtUXB9m2VRU8gg4fo0Nanve9209znO61XVS+8oLk24YKpoldrLRudA9OxF1b/AKVRPAx6ZXVS6nLlvGxtNYyt9MowTzuNJv2cyYAFO5y2Cvo7o6/UbpnUdRp02w5dInomm/sXdv69ewmLqvKhT31HJUNLsYX1wqEqm5nlwzl9nNFxA1Y8pqf7xL+uo8pqf7xL+upE/txf5Pj9i1fuRL/zf7fubTg1nslvvl6qH09sjqal7Gq52y9dGp2qq6f1PDJNWRyOjklnY9qqjmucqKipxRUPT1rCz6Ph4/Y8LYxOTgrhZXNY/wD0T/PtU/GWhbqmqUaKqf53GW9z/wDkd3/SRexxUskj5F2pHuevDVy6lte5/wDyO7/pIvY41LOt6a/VTGM5+RLaxafg9CdDOd3HH/6RaIB4L9eLfY7c+vuM6RRN3InFz1+i1OalmlJRWXyOaU6cqklCCy30PeCp7FmlLPipUuMbKe0z6MjTisC8nKvPXn1cuG+12qjmo5qoqKmqKnBTBb3VO4TcHyN3UNMuNPlGNdYys/bxXUpvN/B/kFQ+/wBti/4WZ2tTG1P+U9fnJ+avqXvK3NqamCGpp5KeojbJFI1WPY5NUci8UNfsxMKy4Zu+zGjn0E6q6nkXl1sXtT1pvILVLH0b9LBcHz7i87L65+IgrSs/XXJ9q7PFfI3y9y/X2yvyKwx+C3R7FPS9BOxi72TNcvSIqclVyq7uci8yyz5uZHZq3zK7EqVtErqq1VDkbX0DnaNmanzm/RenJ3gu5T6EYHxVY8aYapcQ4erG1VDUN3LwdG5OLHp81yc0/gqKc41GznQqOfNPqdBo1FJYKl93BbrtX5LJLbWyPgo7lFUVzGIqr0KNe3VdOSPcxV9PI0PPrFUQw1FPJT1ETJoZWqySN7Uc17VTRUVF3KipyNIfdQ5DTYLqJ8W4Sp3zYbldtVFO3Vzre5V9KxKvBfm8F5Ku7pF5CK9DLh2GO4pt+sjXs3W9yvnzFienpcFYwqWx32JqR0VZIuiVzUTc1y/2un63fx0pOUUj4pWyxPcyRjkc1zV0VqpwVF5KS93awuYbsvYzXhNweUfWMrbPnKSzZo4d6GbYo73StXyCvRu9i8ejfpvdGq8uXFOaLXvuVs9/xvZBgvF0+mII2KlHVu4VzWpqqO/7qIir+cia8eOxZUpwq2dXHJo3041Inyxxhhu84RxFV2C/0T6OvpXbL2O4KnJzV4Oaqb0VOJiD6OZ85SWbNHDvQzbFHe6Vq+QV6N3sXj0b9N7o1Xly4pzRfnvi2wXTC2JK7D96plp7hQyrFNHrqmvFFReaKioqLzRULRY30bqPZJc0aVWm4PuMWADfMQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABcuVeOY66CKx3eZG1jERsEz13TJyaq/S9vfxsg1TRVRdUXRSwMIZnXG2MZSXeN1wpm7kk2vhmJ3r8bx39pP2OqqKUK3v8AMoeubKynN17Nc+cfLy93YXYYW6YUw5c3rJWWelfI74z2t2HL3q3RVOizY1wzdWt6C6wRSL/9udejdr1eduXw1M+x7JGI9jmuavBUXVFJpOlXj0kveUtxurKfHehL2pkYiy9wfG9HtszVVPpTyuT0K4z1ttlutsSx2+hp6Vi8UijRuvfpxPWY+5Xq0W5rlrrnSU+nFr5UR3gnFT4qVGlxSS9yPs7m7u/UlOU+7LZkCO5kua3A12VzkTWDRNV56oR/EGatmpGujtMEtwl5PVFjjTxXevo8SrsT4nvGIqjpLjUqsbV1ZAzzY2dydfauqkfealRjBwi8t9nmWDRtm7ypWhWqrcimnx5vHd54MKTfKfFbLBdH0Vc/Zt9WqbTl4RP4I7u5L4LyIQCuUa0qM1OPNHRby0p3lCVGquD/AFk2sa5rmo5rkc1U1RUXVFQ4VEMNTA+CoiZLE9NHse1HNcnUqKUTgnMC5YfYyjqGrXW9NyRudo+NPzV6uxfUWpZceYYujW7FyjpZV4x1Xwap2ar5q+Clrt9Qo11zw+xnK9Q2fvbGbai5R6NfXqv1xPDcsssLVkjnxwVNGrt6pTy6J6HIqIcKHK7C1M9HSsrKvTlNNon+lGkzgngnbtQTRyt62ORyeo/Kiogp27c88UTet70anrMn4O2zvbiNZaxqKXo/Sy97z5nGho6WgpmUtFTxU8LPisjajUT0FX5vYLjZHNiO2NZGiedVwpoiLqvx07etOfHrJdfcfYZtTHf/AFBlZMnCKl+EVfH4qekqPG+NLlieRInolNQsdqynYuuq9bl5r6jR1K4tvROm+L6Y6E5s5p+pfilXScY9W+q7MdfEi5KstMUfi1fNqoVy0FSiMqETfs9T0Ts1XwVSKgrtKrKlNTjzR0S6tqd1RlRqLMWbU080VRAyeCRksUjUcx7V1RyLwVFOUjGSMdHIxr2OTRzXJqip1Ka/YKxxdcNKlOmlXQa6rTvdps9rV+b607C17HmDhi6Majq5KKZU3x1Xmaf5vi+stVtqNGusN4fYzlepbO3llNuMXKPRr6rp8jruuW2Fa+R0jaWWjc7evk0myn6q6ongh5aTKzC8D0dKtfUp9GWZET/SiKTSmqqapbtU1RDM1U11jejk9RzmmihbtTSsjb1vciJ6zM7O2b3txGmtX1GC9Gqsve8+Z0Wu3UNrpUpbfSRU0KLrsxt01XrXrXtUgObWC466nmv9ta1lXE3aqWaoiStRN7v8SJ6U7eMkveOMM2qNyy3OKokThFTL0jlXq3bk8VQqbHOPLjiNFpIWLR2/X/ktdq6TtevPu4d5qahcWypOm+PYl08iX0Cw1OV2riGYrq5dV173n9Mh5bnuf/yO7/pIvY4qMtPIuvoaOkuqVlbTU6vki2UllazXc7hqpDaW0rmLff8AIuO08XLTKiisvh/yRbRSufUki4qo4lkcsbaFrkZruRVe/VdOtdE9CFtfhyy/XFv/APUs/qU7nbV0tZiullpKmGoYlCxquiejkRekk3apz3oTOrTi7dpPqim7KUZx1FOUWuD6EELZyexjtJHhy5y+cm6jlcvH/tr/AA9HUVMfrHOY9HscrXNXVFRdFRSvWtzK3qKcToOp6dS1Cg6NT2PsfabWGNxJZqO/Wia21rdWSJq1ycY3Jwcnan/wRfLnHNJdrT0F3q4Kevp0Rr3SvRiTN5OTXn1p48yVfhyy/XFv/wDUs/qW6FalXp5zwZySrZ3djcOO61KL5r4NGuWIrRWWO7TW2tZpJGu5ycHt5OTsUmWR2at8yuxKlbRK6qtVQ5G19A52jZmp85v0Xpyd4LuUmOY1BYMTWj4K7W1twp0V1O9alibXWxd/BfUviUa5Fa5WrxRdFKlqNlGnJwfGLOqaJqjvqCnJYmua+q7mfUnA+KrHjTDVLiHD1Y2qoahu5eDo3JxY9PmuTmn8FRTMVEMNRTyU9REyaGVqskje1HNe1U0VFRdyoqcj5v5HZq3zK7EqVtErqq1VDkbX0DnaNmanzm/RenJ3gu5Te/C2amXuI7Iy70GLLTHCrEfJHVVTIZYOtHscqK3Rd2vDqVUKPe6fO3n6vGPQs1OqprjzNPfdZ5S0OXOJKO6WHabZLw6RY6dd/ksrdFcxF5tVHat5poqctVpA2D92VmnZMcXi14fwzVMrbdalfJNVsTzJpnaJoxebWonHgquXTciKtTZXYDv+YmKYbBYKfae7zqioei9FTR673vXq6k4qu5CxWk5xtlKtwfealRJzxEkXuY8OXjEGdGHXWuKXo7dWx11ZM1PNihjcjl2l5bWmynXtH0ZIhlNl5YMt8LR2Oxw7T3aPq6t7U6Wqk0+M7s6m8ETxVZeVvULtXNXMVwXI3KVPcQPnl7rPEFqxFnfd6qzzR1FPTxxUrp41RWySRt0cqKnFEXzdfzerQtX3VOf/AE/leBcC1vwO+K53OF3x+ToYnJy5OcnHgm7VV1SJXSbKVP8AjT4Z5IwV6ifqoAAnDWAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB2QzTQrrDLJGvHVjlT2HWByPjSfBnomrayZNmarnkTho6RVPOAfW2+YUVHgkAAfD6AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAem2UU9xuVLb6VqOnqpmQxIvNznI1PWp9K8o8vLFlvhOGx2aFHSqiPratzfhKqXTe53Zx0bwRPFV+aVBVT0NfT11K/Ynp5WyxO+i5qoqL6UN/su/dD5c4lw/DVXW+0lhubY08qpKxyxo1+m/Ycu57dddNF160QhdZhWnGKgsrrg2LdxTeS4DUf3VOf8A0/leBcC1vwO+K53OF3x+ToYnJy5OcnHgm7VV8/umPdFwXehlwll5WyLRzNVtfdGNcxZWrxii10VG9bufBN29dWjFpumYxVrLwXmeq1b+2IABPmqAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/Z";

function Logo({ sm }:{ sm?:boolean }) {
  return (
    <img
      src={LOGO_SRC}
      alt="Create Geek"
      style={{ height: sm ? 36 : 46, width:"auto", display:"block" }}
    />
  );
}

/* ─────────────────────────────────────────────────────────────
   NAVBAR
───────────────────────────────────────────────────────────── */
function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);
  useEffect(()=>{
    const h = ()=>setScrolled(window.scrollY>24);
    window.addEventListener("scroll",h); return ()=>window.removeEventListener("scroll",h);
  },[]);
  const links = [["Servicios","#servicios"],["Cómo Funciona","#proceso"],["Resultados","#resultados"],["Precios","#precios"]];
  return (
    <nav style={{ position:"fixed", top:0, left:0, right:0, zIndex:99,
      background: scrolled?"rgba(5,7,9,0.92)":"transparent",
      backdropFilter: scrolled?"blur(24px)":"none",
      borderBottom: scrolled?"1px solid rgba(255,255,255,0.06)":"none",
      transition:"all .3s" }}>
      <div style={{ maxWidth:1280, margin:"0 auto", padding:"0 28px", display:"flex", alignItems:"center", justifyContent:"space-between", height:64 }}>
        <Logo sm />
        <div style={{ display:"flex", gap:28, alignItems:"center" }} className="nav-desktop">
          {links.map(([l,h])=>(
            <a key={l} href={h} style={{ color:"rgba(255,255,255,0.5)", fontSize:13, fontFamily:"DM Sans, sans-serif", textDecoration:"none", transition:"color .2s" }}
              onMouseEnter={e=>e.currentTarget.style.color="white"} onMouseLeave={e=>e.currentTarget.style.color="rgba(255,255,255,0.5)"}>{l}</a>
          ))}
        </div>
        <div style={{ display:"flex", gap:10, alignItems:"center" }} className="nav-desktop">
          <a href="#contacto" style={{ color:"rgba(255,255,255,0.6)", fontSize:13, fontFamily:"DM Sans, sans-serif", textDecoration:"none", padding:"8px 16px" }}>Contacto</a>
          <a href="#precios" style={{ background:"#0095B1", color:"white", fontSize:13, fontWeight:700, fontFamily:"DM Sans, sans-serif", textDecoration:"none",
            padding:"9px 20px", borderRadius:8, transition:"opacity .2s" }}>Empezar →</a>
        </div>
      </div>
    </nav>
  );
}

/* ─────────────────────────────────────────────────────────────
   HERO  (GitHub email capture + Nuxt code snippet + particles)
───────────────────────────────────────────────────────────── */
function Hero() {
  const [email, setEmail] = useState("");
  const canvasRef = useRef<HTMLCanvasElement>(null);

  // Particle animation (GitHub-inspired)
  useEffect(()=>{
    const canvas = canvasRef.current; if(!canvas) return;
    const ctx = canvas.getContext("2d")!;
    let w=canvas.width=canvas.offsetWidth, h=canvas.height=canvas.offsetHeight;
    const pts = Array.from({length:60},()=>({
      x:Math.random()*w, y:Math.random()*h,
      vx:(Math.random()-.5)*.4, vy:(Math.random()-.5)*.4,
      r:Math.random()*1.5+.5,
    }));
    let raf:number;
    const draw = ()=>{
      ctx.clearRect(0,0,w,h);
      pts.forEach(p=>{
        p.x+=p.vx; p.y+=p.vy;
        if(p.x<0||p.x>w) p.vx*=-1;
        if(p.y<0||p.y>h) p.vy*=-1;
        ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle="rgba(0,149,177,0.5)"; ctx.fill();
      });
      pts.forEach((a,i)=>pts.slice(i+1).forEach(b=>{
        const d=Math.hypot(a.x-b.x,a.y-b.y);
        if(d<110){ ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y);
          ctx.strokeStyle=`rgba(0,149,177,${.12*(1-d/110)})`; ctx.lineWidth=.6; ctx.stroke(); }
      }));
      raf=requestAnimationFrame(draw);
    };
    draw();
    const onResize=()=>{ w=canvas.width=canvas.offsetWidth; h=canvas.height=canvas.offsetHeight; };
    window.addEventListener("resize",onResize);
    return ()=>{ cancelAnimationFrame(raf); window.removeEventListener("resize",onResize); };
  },[]);

  const codeLines = [
    { t:"comment", v:"// Tu ecosistema digital en producción" },
    { t:"kw", v:"const" },    { t:"var", v:" business" },   { t:"op", v:" = {" },
    { t:"key", v:"  sitioWeb" },  { t:"op", v:": " }, { t:"str", v:'"activo · 97/100 Lighthouse"' }, { t:"op", v:"," },
    { t:"key", v:"  chatbotIA" }, { t:"op", v:": " }, { t:"str", v:'"24/7 · WhatsApp + Web"' },      { t:"op", v:"," },
    { t:"key", v:"  crmGHL" },   { t:"op", v:": " }, { t:"str", v:'"pipeline automatizado"' },       { t:"op", v:"," },
    { t:"key", v:"  leads" },    { t:"op", v:": " }, { t:"num", v:"monthlyleads" }, { t:"op", v:" * " }, { t:"num", v:"3" },  { t:"op", v:"," },
    { t:"op", v:"}" },
    { t:"blank", v:"" },
    { t:"kw", v:"// Resultado:" },
    { t:"fn", v:"business.revenue" }, { t:"op", v:"()" }, { t:"comment2", v:" // → +40% en 90 días" },
  ];

  return (
    <section style={{ position:"relative", minHeight:"100vh", display:"flex", alignItems:"center", overflow:"hidden", background:"#050709" }}>
      <canvas ref={canvasRef} style={{ position:"absolute", inset:0, width:"100%", height:"100%", pointerEvents:"none" }}/>

      {/* Gradient overlay */}
      <div style={{ position:"absolute", inset:0, background:"radial-gradient(ellipse 80% 50% at 50% 40%, rgba(0,149,177,0.1) 0%, transparent 70%)", pointerEvents:"none" }}/>
      <div style={{ position:"absolute", bottom:0, left:0, right:0, height:200, background:"linear-gradient(transparent, #050709)", pointerEvents:"none" }}/>

      <div style={{ position:"relative", maxWidth:1280, margin:"0 auto", padding:"120px 28px 80px", display:"grid", gridTemplateColumns:"1fr 1fr", gap:64, alignItems:"center" }}>

        {/* LEFT */}
        <div>
          {/* Badge — Platzi-inspired */}
          <div style={{ display:"inline-flex", alignItems:"center", gap:8, background:"linear-gradient(135deg,rgba(0,149,177,.15),rgba(168,85,247,.1))",
            border:"1px solid rgba(0,149,177,.35)", borderRadius:99, padding:"6px 16px", marginBottom:28 }}>
            <span style={{ width:6, height:6, borderRadius:"50%", background:"#0095B1", animation:"pulse 2s infinite", display:"block" }}/>
            <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:3, textTransform:"uppercase", color:"#0095B1" }}>
              Agencia Digital · Venezuela 🇻🇪
            </span>
          </div>

          {/* Headline — Montserrat Black */}
          <h1 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(40px,6vw,72px)", lineHeight:1.0, letterSpacing:"-2.5px", color:"white", marginBottom:20 }}>
            Tu negocio en<br/>
            <span style={{ background:"linear-gradient(135deg, #0095B1, #00c8e8)", WebkitBackgroundClip:"text", WebkitTextFillColor:"transparent" }}>piloto</span>
            {" "}<span style={{ color:"#F1E498" }}>automático.</span>
          </h1>

          <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:17, fontWeight:300, color:"rgba(255,255,255,0.52)", lineHeight:1.75, maxWidth:520, marginBottom:36 }}>
            Web de impacto + Chatbot IA 24/7 + CRM automatizado. El ecosistema digital que convierte leads en clientes mientras te enfocas en tu negocio.
          </p>

          {/* GitHub-style email capture */}
          <div style={{ display:"flex", gap:10, marginBottom:24, flexWrap:"wrap" }}>
            <input value={email} onChange={e=>setEmail(e.target.value)}
              placeholder="tu@empresa.com"
              style={{ flex:1, minWidth:220, background:"rgba(255,255,255,0.05)", border:"1px solid rgba(255,255,255,0.12)",
                color:"white", borderRadius:8, padding:"12px 16px", fontSize:14, fontFamily:"DM Sans, sans-serif", outline:"none" }}/>
            <a href="#contacto" style={{ background:"#0095B1", color:"white", fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:14,
              padding:"12px 24px", borderRadius:8, textDecoration:"none", whiteSpace:"nowrap", transition:"opacity .2s" }}>
              Empezar gratis →
            </a>
          </div>
          <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:12, color:"rgba(255,255,255,0.25)" }}>
            Sin tarjeta de crédito · Diagnóstico gratuito · Respuesta en 24h
          </p>

          {/* Stats — GitHub-inspired */}
          <div style={{ display:"flex", gap:32, marginTop:40, paddingTop:32, borderTop:"1px solid rgba(255,255,255,0.06)", flexWrap:"wrap" }}>
            {[{v:"+120",l:"proyectos"},{v:"24/7",l:"chatbots activos"},{v:"3x",l:"conversiones"},{v:"48h",l:"time-to-launch"}].map(s=>(
              <div key={s.l}>
                <div style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:28, color:"white", lineHeight:1 }}>{s.v}</div>
                <div style={{ fontFamily:"DM Sans, sans-serif", fontSize:11, color:"rgba(255,255,255,0.3)", marginTop:4 }}>{s.l}</div>
              </div>
            ))}
          </div>
        </div>

        {/* RIGHT — Nuxt-style code UI */}
        <div style={{ position:"relative" }}>
          {/* Glow behind */}
          <div style={{ position:"absolute", inset:-40, background:"radial-gradient(circle, rgba(0,149,177,0.12) 0%, transparent 70%)", pointerEvents:"none" }}/>

          <div style={{ position:"relative", borderRadius:16, overflow:"hidden", border:"1px solid rgba(255,255,255,0.1)",
            background:"linear-gradient(145deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01))",
            backdropFilter:"blur(20px)" }}>
            {/* Title bar */}
            <div style={{ display:"flex", alignItems:"center", justifyContent:"space-between", padding:"12px 18px",
              background:"rgba(0,0,0,0.3)", borderBottom:"1px solid rgba(255,255,255,0.06)" }}>
              <div style={{ display:"flex", gap:7 }}>
                {["#ff5f57","#ffbd2e","#28c940"].map(c=><div key={c} style={{ width:10, height:10, borderRadius:"50%", background:c }}/>)}
              </div>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:11, color:"rgba(255,255,255,0.3)" }}>
                ecosystem.config.ts
              </span>
              <div style={{ display:"flex", gap:6 }}>
                <div style={{ background:"rgba(0,149,177,0.2)", border:"1px solid rgba(0,149,177,0.4)", borderRadius:4,
                  padding:"2px 8px", fontFamily:"DM Mono, monospace", fontSize:9, color:"#0095B1" }}>LIVE ●</div>
              </div>
            </div>

            {/* Code content */}
            <div style={{ padding:"20px 22px", fontFamily:"DM Mono, monospace", fontSize:12.5, lineHeight:1.9 }}>
              <div style={{ color:"rgba(255,255,255,0.2)", marginBottom:8 }}>// Tu ecosistema digital en producción</div>
              <div><span style={{ color:"#c792ea" }}>const </span><span style={{ color:"#82aaff" }}>business</span><span style={{ color:"rgba(255,255,255,0.5)" }}> = {"{"}</span></div>
              {[
                ["sitioWeb", '"activo · 97/100 Lighthouse ✓"', "#F1E498"],
                ["chatbotIA", '"24/7 · WhatsApp + Web ✓"', "#0095B1"],
                ["crmGHL", '"pipeline automatizado ✓"', "#4ade80"],
                ["leadsPerMonth", "leadsBase * 3", "#c792ea"],
                ["revenueGrowth", '"+40% en 90 días ✓"', "#F1E498"],
              ].map(([k,v,col])=>(
                <div key={k} style={{ marginLeft:16 }}>
                  <span style={{ color:"#80cbc4" }}>{k}</span>
                  <span style={{ color:"rgba(255,255,255,0.4)" }}>: </span>
                  <span style={{ color: col as string }}>{v}</span>
                  <span style={{ color:"rgba(255,255,255,0.3)" }}>,</span>
                </div>
              ))}
              <div style={{ color:"rgba(255,255,255,0.5)" }}>{"}"}</div>
              <div style={{ marginTop:10 }}>
                <span style={{ color:"#82aaff" }}>business</span>
                <span style={{ color:"rgba(255,255,255,0.4)" }}>.</span>
                <span style={{ color:"#c792ea" }}>scale</span>
                <span style={{ color:"rgba(255,255,255,0.4)" }}>()</span>
                <span style={{ color:"rgba(255,255,255,0.2)", marginLeft:8 }}>// 🚀 running...</span>
              </div>
            </div>

            {/* Status bar */}
            <div style={{ padding:"8px 18px", background:"rgba(0,149,177,0.08)", borderTop:"1px solid rgba(0,149,177,0.15)",
              display:"flex", gap:16, alignItems:"center" }}>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, color:"#4ade80" }}>● Deployed</span>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, color:"rgba(255,255,255,0.3)" }}>creategeek.agency</span>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, color:"rgba(255,255,255,0.2)", marginLeft:"auto" }}>v2.4.1</span>
            </div>
          </div>
        </div>
      </div>

      <style>{`@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}`}</style>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   MARQUEE (GitHub customers style)
───────────────────────────────────────────────────────────── */
function Marquee() {
  return (
    <div style={{ background:"#0a0d10", borderTop:"1px solid rgba(255,255,255,0.05)", borderBottom:"1px solid rgba(255,255,255,0.05)", padding:"36px 0", overflow:"hidden" }}>
      <p style={{ textAlign:"center", fontFamily:"DM Sans, sans-serif", fontSize:11, color:"rgba(255,255,255,0.2)", letterSpacing:3, textTransform:"uppercase", marginBottom:20 }}>
        Integramos con las herramientas que ya usas
      </p>
      <div style={{ display:"flex", overflow:"hidden" }}>
        <div style={{ display:"flex", gap:14, animation:"scroll 28s linear infinite", whiteSpace:"nowrap", width:"max-content" }}>
          {[...INTEGRATIONS,...INTEGRATIONS].map((n,i)=>(
            <div key={i} style={{ display:"inline-flex", alignItems:"center", gap:8, padding:"7px 20px", borderRadius:99, background:"rgba(255,255,255,0.03)",
              border:"1px solid rgba(255,255,255,0.07)", color:"rgba(255,255,255,0.4)", fontFamily:"DM Sans, sans-serif", fontSize:13, fontWeight:500 }}>
              {n}
            </div>
          ))}
        </div>
      </div>
      <style>{`@keyframes scroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}`}</style>
    </div>
  );
}

/* ─────────────────────────────────────────────────────────────
   SERVICES (Platzi card style with gradients)
───────────────────────────────────────────────────────────── */
function Services() {
  return (
    <section id="servicios" style={{ padding:"96px 28px", background:"#050709" }}>
      <div style={{ maxWidth:1280, margin:"0 auto" }}>
        <div style={{ marginBottom:52 }}>
          <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:4, textTransform:"uppercase", color:"#F1E498" }}>// Servicios</span>
          <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(32px,5vw,54px)", lineHeight:1.05, letterSpacing:-1.5, color:"white", margin:"12px 0 14px" }}>
            El ecosistema digital<br/><span style={{ color:"#0095B1" }}>que tu negocio necesita</span>
          </h2>
          <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:15, fontWeight:300, color:"rgba(255,255,255,0.45)", maxWidth:520, lineHeight:1.7 }}>
            Tres capas de tecnología integradas. Presencia, conversación y automatización — todo en uno.
          </p>
        </div>

        <div style={{ display:"grid", gridTemplateColumns:"repeat(auto-fit,minmax(280px,1fr))", gap:16 }}>
          {SERVICES.map(s=>(
            <div key={s.title} style={{ position:"relative", borderRadius:16, padding:28, overflow:"hidden",
              background:`linear-gradient(145deg, ${s.bg}, rgba(255,255,255,0.01))`,
              border:`1px solid ${s.color}30`, transition:"transform .2s, box-shadow .2s", cursor:"pointer" }}
              onMouseEnter={e=>{ e.currentTarget.style.transform="translateY(-4px)"; e.currentTarget.style.boxShadow=`0 20px 40px ${s.color}20`; }}
              onMouseLeave={e=>{ e.currentTarget.style.transform="translateY(0)"; e.currentTarget.style.boxShadow="none"; }}>

              {/* Platzi-style gradient stripe */}
              <div style={{ position:"absolute", top:0, left:0, right:0, height:2, background:`linear-gradient(90deg, ${s.color}, transparent)` }}/>

              <div style={{ display:"flex", justifyContent:"space-between", alignItems:"flex-start", marginBottom:20 }}>
                <div style={{ width:52, height:52, borderRadius:12, display:"flex", alignItems:"center", justifyContent:"center",
                  fontSize:22, background:s.bg, border:`1px solid ${s.color}30` }}>{s.icon}</div>
                <span style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:2, textTransform:"uppercase",
                  padding:"4px 10px", borderRadius:99, background:s.bg, border:`1px solid ${s.color}30`, color:s.color }}>
                  {s.tag}
                </span>
              </div>

              <h3 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:18, color:"white", marginBottom:10, letterSpacing:-0.3 }}>{s.title}</h3>
              <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.5)", lineHeight:1.7, marginBottom:20 }}>{s.desc}</p>

              {/* Metric — Platzi achievement style */}
              <div style={{ display:"flex", alignItems:"center", gap:10, padding:"10px 14px", borderRadius:10, background:"rgba(0,0,0,0.25)" }}>
                <span style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:22, color:s.color }}>{s.metric}</span>
                <span style={{ fontFamily:"DM Sans, sans-serif", fontSize:12, color:"rgba(255,255,255,0.35)" }}>{s.metricLabel}</span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   FEATURES TABS (Nuxt tab system + code preview)
───────────────────────────────────────────────────────────── */
function Features() {
  const [active, setActive] = useState(0);
  const f = FEATURES_TABS[active];
  return (
    <section style={{ padding:"96px 28px", background:"#0a0d10" }}>
      <div style={{ maxWidth:1280, margin:"0 auto" }}>
        <div style={{ textAlign:"center", marginBottom:52 }}>
          <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:4, textTransform:"uppercase", color:"#0095B1" }}>// Cómo lo hacemos</span>
          <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(32px,5vw,54px)", lineHeight:1.05, letterSpacing:-1.5, color:"white", margin:"12px 0" }}>
            Tecnología real,<br/>resultados medibles
          </h2>
        </div>

        {/* Tab bar — Nuxt style */}
        <div style={{ display:"flex", gap:4, justifyContent:"center", marginBottom:48, flexWrap:"wrap" }}>
          {FEATURES_TABS.map((t,i)=>(
            <button key={t.id} onClick={()=>setActive(i)}
              style={{ fontFamily:"DM Sans, sans-serif", fontWeight:600, fontSize:13, padding:"9px 20px", borderRadius:8, border:"none", cursor:"pointer", transition:"all .2s",
                background: active===i?"#0095B1":"rgba(255,255,255,0.05)",
                color: active===i?"white":"rgba(255,255,255,0.5)" }}>
              {t.label}
            </button>
          ))}
        </div>

        <div style={{ display:"grid", gridTemplateColumns:"1fr 1fr", gap:48, alignItems:"start" }}>
          {/* Left: info */}
          <div style={{ paddingTop:8 }}>
            <h3 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:"clamp(24px,3.5vw,38px)", lineHeight:1.1, letterSpacing:-1, color:"white", marginBottom:16 }}>
              {f.headline}
            </h3>
            <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:15, fontWeight:300, color:"rgba(255,255,255,0.5)", lineHeight:1.75, marginBottom:28 }}>{f.sub}</p>
            <ul style={{ listStyle:"none", display:"flex", flexDirection:"column", gap:12 }}>
              {f.checks.map(c=>(
                <li key={c} style={{ display:"flex", alignItems:"center", gap:12, fontFamily:"DM Sans, sans-serif", fontSize:14, color:"rgba(255,255,255,0.65)" }}>
                  <div style={{ width:20, height:20, borderRadius:"50%", flexShrink:0,
                    background:"rgba(0,149,177,0.12)", border:"1px solid rgba(0,149,177,0.3)", display:"flex", alignItems:"center", justifyContent:"center",
                    fontSize:10, color:"#0095B1" }}>✓</div>
                  {c}
                </li>
              ))}
            </ul>
            <a href="#contacto" style={{ display:"inline-block", marginTop:28, fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:14,
              background:"rgba(0,149,177,0.15)", border:"1px solid rgba(0,149,177,0.35)", color:"#0095B1",
              padding:"11px 24px", borderRadius:8, textDecoration:"none" }}>
              Ver demo gratuita →
            </a>
          </div>

          {/* Right: code */}
          <div style={{ borderRadius:14, overflow:"hidden", border:"1px solid rgba(255,255,255,0.08)", background:"rgba(0,0,0,0.35)" }}>
            <div style={{ display:"flex", alignItems:"center", justifyContent:"space-between", padding:"11px 18px", background:"rgba(255,255,255,0.03)", borderBottom:"1px solid rgba(255,255,255,0.06)" }}>
              <div style={{ display:"flex", gap:6 }}>
                {["#ff5f57","#ffbd2e","#28c940"].map(c=><div key={c} style={{ width:9, height:9, borderRadius:"50%", background:c }}/>)}
              </div>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:11, color:"rgba(255,255,255,0.25)" }}>
                {f.id}.config.ts
              </span>
              <div style={{ width:9 }}/>
            </div>
            <pre style={{ padding:"20px 22px", fontFamily:"DM Mono, monospace", fontSize:12, lineHeight:1.9,
              color:"rgba(255,255,255,0.55)", overflowX:"auto", margin:0, whiteSpace:"pre-wrap" }}>
              {f.code}
            </pre>
            <div style={{ padding:"8px 18px", background:"rgba(74,222,128,0.06)", borderTop:"1px solid rgba(74,222,128,0.15)", display:"flex", alignItems:"center", gap:8 }}>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, color:"#4ade80" }}>● running</span>
              <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, color:"rgba(255,255,255,0.2)", marginLeft:"auto" }}>No errors · 0 warnings</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   HOW IT WORKS (GitHub accordion style)
───────────────────────────────────────────────────────────── */
function Process() {
  const [expanded, setExpanded] = useState(0);
  return (
    <section id="proceso" style={{ padding:"96px 28px", background:"#050709" }}>
      <div style={{ maxWidth:1280, margin:"0 auto", display:"grid", gridTemplateColumns:"1fr 1fr", gap:72, alignItems:"start" }}>
        <div>
          <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:4, textTransform:"uppercase", color:"#F1E498" }}>// Proceso</span>
          <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(32px,5vw,52px)", lineHeight:1.05, letterSpacing:-1.5, color:"white", margin:"12px 0 14px" }}>
            De 0 a digital<br/>en 3 pasos
          </h2>
          <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:15, fontWeight:300, color:"rgba(255,255,255,0.45)", lineHeight:1.7, marginBottom:40 }}>
            Un proceso claro sin tecnicismos. Diseñado para emprendedores con poco tiempo y grandes objetivos.
          </p>

          {/* GitHub-style accordion */}
          {STEPS.map((s,i)=>(
            <div key={s.num} onClick={()=>setExpanded(i)} style={{ cursor:"pointer", marginBottom:8 }}>
              <div style={{ display:"flex", alignItems:"center", gap:16, padding:"16px 20px", borderRadius:12,
                background: expanded===i?"rgba(0,149,177,0.1)":"rgba(255,255,255,0.03)",
                border:`1px solid ${expanded===i?"rgba(0,149,177,0.3)":"rgba(255,255,255,0.06)"}`,
                transition:"all .2s" }}>
                <div style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:12, color:"#0095B1",
                  background:"rgba(0,149,177,0.12)", border:"1px solid rgba(0,149,177,0.25)", borderRadius:6, padding:"3px 8px", flexShrink:0 }}>
                  {s.num}
                </div>
                <span style={{ fontSize:22 }}>{s.icon}</span>
                <div style={{ fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:16, color:"white" }}>{s.title}</div>
                <div style={{ marginLeft:"auto", color:"rgba(255,255,255,0.3)", fontSize:12, transform: expanded===i?"rotate(180deg)":"rotate(0deg)", transition:"transform .2s" }}>▼</div>
              </div>
              {expanded===i && (
                <div style={{ padding:"16px 20px 16px 64px", fontFamily:"DM Sans, sans-serif", fontSize:14, color:"rgba(255,255,255,0.5)", lineHeight:1.7 }}>
                  {s.desc}
                </div>
              )}
            </div>
          ))}

          <a href="#contacto" style={{ display:"inline-block", marginTop:24, fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:14,
            background:"#0095B1", color:"white", padding:"13px 28px", borderRadius:10, textDecoration:"none" }}>
            Agendar diagnóstico gratuito →
          </a>
        </div>

        {/* Timeline visual */}
        <div style={{ paddingTop:60, display:"flex", flexDirection:"column", gap:0 }}>
          {STEPS.map((s,i)=>(
            <div key={s.num} style={{ display:"flex", gap:20 }}>
              <div style={{ display:"flex", flexDirection:"column", alignItems:"center" }}>
                <div style={{ width:44, height:44, borderRadius:"50%", flexShrink:0,
                  background: expanded===i?"#0095B1":"rgba(255,255,255,0.04)",
                  border:`2px solid ${expanded===i?"#0095B1":"rgba(255,255,255,0.1)"}`,
                  display:"flex", alignItems:"center", justifyContent:"center", fontSize:18, transition:"all .3s" }}>
                  {s.icon}
                </div>
                {i<STEPS.length-1 && <div style={{ width:2, flex:1, background:expanded>i?"rgba(0,149,177,0.5)":"rgba(255,255,255,0.06)", marginTop:4, marginBottom:4, minHeight:64, transition:"background .3s" }}/>}
              </div>
              <div style={{ paddingBottom:i<STEPS.length-1?64:0, paddingTop:8 }}>
                <p style={{ fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:15, color: expanded===i?"white":"rgba(255,255,255,0.4)", marginBottom:6, transition:"color .2s" }}>
                  {s.title}
                </p>
                <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.3)", lineHeight:1.6 }}>{s.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   TESTIMONIALS (Nuxt quote style + Platzi community feel)
───────────────────────────────────────────────────────────── */
function Testimonials() {
  return (
    <section id="resultados" style={{ padding:"96px 28px", background:"#0a0d10" }}>
      <div style={{ maxWidth:1280, margin:"0 auto" }}>
        <div style={{ textAlign:"center", marginBottom:52 }}>
          <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:4, textTransform:"uppercase", color:"#F1E498" }}>// Resultados reales</span>
          <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(32px,5vw,54px)", lineHeight:1.05, letterSpacing:-1.5, color:"white", margin:"12px 0" }}>
            Lo que dicen los que<br/><span style={{ color:"#0095B1" }}>ya dan el paso</span>
          </h2>
        </div>

        <div style={{ display:"grid", gridTemplateColumns:"repeat(auto-fit,minmax(280px,1fr))", gap:16 }}>
          {TESTIMONIALS.map((t,i)=>(
            <div key={t.name} style={{ borderRadius:16, padding:28, position:"relative", overflow:"hidden",
              background:"rgba(255,255,255,0.02)", border:"1px solid rgba(255,255,255,0.07)",
              ...(i===0 ? { gridRow:"span 1" } : {}) }}>
              {/* Gradient accent top */}
              <div style={{ position:"absolute", top:0, left:0, right:0, height:2, background:`linear-gradient(90deg,${t.color},transparent)` }}/>

              <div style={{ fontSize:36, color:`${t.color}30`, fontFamily:"Montserrat, sans-serif", fontWeight:900, lineHeight:1, marginBottom:14 }}>"</div>
              <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:14, fontWeight:300, color:"rgba(255,255,255,0.7)", lineHeight:1.75, marginBottom:24 }}>{t.text}</p>
              <div style={{ display:"flex", alignItems:"center", gap:12 }}>
                <div style={{ width:40, height:40, borderRadius:"50%", background:t.color, display:"flex", alignItems:"center", justifyContent:"center",
                  fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:13, color: t.color==="#F1E498"?"#050709":"white", flexShrink:0 }}>
                  {t.avatar}
                </div>
                <div>
                  <div style={{ fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:13, color:"white" }}>{t.name}</div>
                  <div style={{ fontFamily:"DM Sans, sans-serif", fontSize:11, color:"rgba(255,255,255,0.3)", marginTop:2 }}>{t.role}</div>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* GitHub-style social proof numbers */}
        <div style={{ display:"grid", gridTemplateColumns:"repeat(4,1fr)", gap:1, marginTop:40, background:"rgba(255,255,255,0.06)", border:"1px solid rgba(255,255,255,0.06)", borderRadius:16, overflow:"hidden" }}>
          {[
            {v:"+120",l:"proyectos entregados",c:"#0095B1"},
            {v:"98%",l:"clientes satisfechos",c:"#4ade80"},
            {v:"4.9⭐",l:"rating promedio",c:"#F1E498"},
            {v:"+500",l:"leads generados/mes",c:"#a855f7"},
          ].map(s=>(
            <div key={s.l} style={{ padding:"28px 20px", background:"#0a0d10", textAlign:"center" }}>
              <div style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:32, color:s.c, lineHeight:1 }}>{s.v}</div>
              <div style={{ fontFamily:"DM Sans, sans-serif", fontSize:12, color:"rgba(255,255,255,0.3)", marginTop:6 }}>{s.l}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   PRICING
───────────────────────────────────────────────────────────── */
function Pricing() {
  return (
    <section id="precios" style={{ padding:"96px 28px", background:"#050709" }}>
      <div style={{ maxWidth:1280, margin:"0 auto" }}>
        <div style={{ textAlign:"center", marginBottom:52 }}>
          <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:4, textTransform:"uppercase", color:"#0095B1" }}>// Inversión</span>
          <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(32px,5vw,54px)", lineHeight:1.05, letterSpacing:-1.5, color:"white", margin:"12px 0 12px" }}>
            Planes que crecen<br/>con tu negocio
          </h2>
          <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:15, fontWeight:300, color:"rgba(255,255,255,0.4)", maxWidth:400, margin:"0 auto" }}>
            Sin costos ocultos. Sin permanencia. Empieza con lo que necesitas hoy.
          </p>
        </div>

        <div style={{ display:"grid", gridTemplateColumns:"repeat(auto-fit,minmax(300px,1fr))", gap:16 }}>
          {PLANS.map(p=>(
            <div key={p.name} style={{ position:"relative", borderRadius:18, padding:32, display:"flex", flexDirection:"column",
              background: p.highlight?"rgba(0,149,177,0.07)":"rgba(255,255,255,0.02)",
              border: p.highlight?`2px solid #0095B1`:"1px solid rgba(255,255,255,0.07)",
              ...(p.highlight?{boxShadow:"0 0 40px rgba(0,149,177,0.12)"}:{}) }}>

              {p.highlight && (
                <div style={{ position:"absolute", top:-14, left:"50%", transform:"translateX(-50%)",
                  background:"linear-gradient(135deg,#0095B1,#00c8e8)", color:"white",
                  fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:10, letterSpacing:2,
                  padding:"5px 18px", borderRadius:99, whiteSpace:"nowrap" }}>✦ MÁS POPULAR</div>
              )}

              {/* Top gradient */}
              <div style={{ position:"absolute", top:0, left:0, right:0, height:2, borderRadius:"18px 18px 0 0", background:`linear-gradient(90deg,${p.color},transparent)` }}/>

              <div style={{ marginBottom:8 }}>
                <span style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:3, textTransform:"uppercase", color:p.color }}>{p.name}</span>
              </div>
              <div style={{ display:"flex", alignItems:"flex-end", gap:6, marginBottom:8 }}>
                <span style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:46, color:"white", lineHeight:1 }}>{p.price}</span>
                <span style={{ fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.3)", paddingBottom:6 }}>{p.period}</span>
              </div>
              <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.45)", lineHeight:1.6, marginBottom:24 }}>{p.desc}</p>

              <ul style={{ listStyle:"none", display:"flex", flexDirection:"column", gap:10, flex:1, marginBottom:28 }}>
                {p.features.map(f=>(
                  <li key={f} style={{ display:"flex", alignItems:"flex-start", gap:10, fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.6)" }}>
                    <div style={{ width:16, height:16, borderRadius:"50%", flexShrink:0, marginTop:1,
                      background:`${p.color}18`, border:`1px solid ${p.color}40`,
                      display:"flex", alignItems:"center", justifyContent:"center", fontSize:8, color:p.color }}>✓</div>
                    {f}
                  </li>
                ))}
              </ul>
              <a href="#contacto" style={{ display:"block", textAlign:"center", padding:"14px", borderRadius:10,
                fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:14, textDecoration:"none", transition:"opacity .2s",
                background: p.highlight?"#0095B1":"rgba(255,255,255,0.05)",
                color: p.highlight?"white":"rgba(255,255,255,0.65)",
                border: p.highlight?"none":`1px solid rgba(255,255,255,0.1)` }}>
                {p.cta}
              </a>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   CONTACT (GitHub email form style)
───────────────────────────────────────────────────────────── */
function Contact() {
  const [form, setForm] = useState({ name:"",email:"",wa:"",service:"",msg:"" });
  const [sent, setSent] = useState(false);
  const set = (k:string) => (e:React.ChangeEvent<any>) => setForm(f=>({...f,[k]:e.target.value}));
  const inp: React.CSSProperties = {
    width:"100%", background:"rgba(255,255,255,0.04)", border:"1px solid rgba(255,255,255,0.1)",
    color:"white", borderRadius:8, padding:"11px 16px", fontSize:14, fontFamily:"DM Sans, sans-serif", outline:"none",
    boxSizing:"border-box" as any,
  };
  return (
    <section id="contacto" style={{ padding:"96px 28px", background:"#0a0d10" }}>
      <div style={{ maxWidth:1280, margin:"0 auto", display:"grid", gridTemplateColumns:"1fr 1fr", gap:72, alignItems:"start" }}>
        <div>
          <span style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:4, textTransform:"uppercase", color:"#F1E498" }}>// Hablemos</span>
          <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(30px,4.5vw,50px)", lineHeight:1.05, letterSpacing:-1.5, color:"white", margin:"12px 0 16px" }}>
            Agenda tu diagnóstico<br/><span style={{ color:"#0095B1" }}>gratuito hoy</span>
          </h2>
          <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:15, fontWeight:300, color:"rgba(255,255,255,0.45)", lineHeight:1.75, marginBottom:40, maxWidth:400 }}>
            30 minutos. Plan concreto. Sin compromiso. Descubre exactamente qué automatizaciones harán crecer tu negocio.
          </p>
          {[
            {icon:"💬",label:"WhatsApp",val:"+58 424-000-0000",color:"#4ade80"},
            {icon:"📧",label:"Email",val:"hola@creategeek.agency",color:"#0095B1"},
            {icon:"📍",label:"Ubicación",val:"Venezuela · Remoto global",color:"#F1E498"},
          ].map(c=>(
            <div key={c.label} style={{ display:"flex", alignItems:"center", gap:16, marginBottom:20 }}>
              <div style={{ width:44, height:44, borderRadius:12, flexShrink:0, display:"flex", alignItems:"center", justifyContent:"center",
                background:`${c.color}15`, border:`1px solid ${c.color}30`, fontSize:18 }}>{c.icon}</div>
              <div>
                <div style={{ fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:2, textTransform:"uppercase", color:"rgba(255,255,255,0.3)" }}>{c.label}</div>
                <div style={{ fontFamily:"DM Sans, sans-serif", fontSize:14, fontWeight:500, color:"white", marginTop:2 }}>{c.val}</div>
              </div>
            </div>
          ))}
        </div>

        <div style={{ background:"rgba(255,255,255,0.02)", border:"1px solid rgba(255,255,255,0.08)", borderRadius:18, padding:32 }}>
          {!sent ? (
            <div>
              <div style={{ display:"grid", gridTemplateColumns:"1fr 1fr", gap:14, marginBottom:14 }}>
                <div><div style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:2, textTransform:"uppercase", color:"rgba(255,255,255,0.3)", marginBottom:7 }}>NOMBRE</div>
                  <input style={inp} placeholder="Tu nombre" value={form.name} onChange={set("name")}/></div>
                <div><div style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:2, textTransform:"uppercase", color:"rgba(255,255,255,0.3)", marginBottom:7 }}>WHATSAPP</div>
                  <input style={inp} placeholder="+58 4XX-XXX" value={form.wa} onChange={set("wa")}/></div>
              </div>
              <div style={{ marginBottom:14 }}>
                <div style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:2, textTransform:"uppercase", color:"rgba(255,255,255,0.3)", marginBottom:7 }}>EMAIL</div>
                <input style={inp} type="email" placeholder="tu@empresa.com" value={form.email} onChange={set("email")}/>
              </div>
              <div style={{ marginBottom:14 }}>
                <div style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:2, textTransform:"uppercase", color:"rgba(255,255,255,0.3)", marginBottom:7 }}>SERVICIO</div>
                <select style={{...inp,cursor:"pointer"}} value={form.service} onChange={set("service")}>
                  <option>Selecciona un servicio</option>
                  <option>Desarrollo Web</option><option>Chatbot IA</option>
                  <option>Automatización CRM</option><option>Ecosistema completo</option>
                </select>
              </div>
              <div style={{ marginBottom:20 }}>
                <div style={{ fontFamily:"DM Mono, monospace", fontSize:9, letterSpacing:2, textTransform:"uppercase", color:"rgba(255,255,255,0.3)", marginBottom:7 }}>MENSAJE</div>
                <textarea style={{...inp,height:100,resize:"none"}} placeholder="Cuéntanos sobre tu negocio..." value={form.msg} onChange={set("msg")}/>
              </div>
              <button onClick={()=>setSent(true)} style={{ width:"100%", background:"#0095B1", color:"white",
                fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:15, padding:"14px",
                borderRadius:10, border:"none", cursor:"pointer", transition:"opacity .2s" }}>
                Solicitar diagnóstico gratuito →
              </button>
            </div>
          ) : (
            <div style={{ textAlign:"center", padding:"48px 0" }}>
              <div style={{ fontSize:56, marginBottom:20 }}>🚀</div>
              <h3 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:22, color:"white", marginBottom:10 }}>¡Mensaje enviado!</h3>
              <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:14, color:"rgba(255,255,255,0.45)" }}>
                Te contactamos en menos de 24 horas con tu plan de ecosistema digital.
              </p>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   CTA BANNER (Platzi vibrant style)
───────────────────────────────────────────────────────────── */
function CTABanner() {
  return (
    <section style={{ position:"relative", padding:"80px 28px", overflow:"hidden",
      background:"linear-gradient(135deg, #0095B1 0%, #006f84 50%, #004a59 100%)", textAlign:"center" }}>
      <div style={{ position:"absolute", inset:0, opacity:.08,
        backgroundImage:"radial-gradient(rgba(241,228,152,1) 1px, transparent 1px)", backgroundSize:"32px 32px", pointerEvents:"none" }}/>
      {/* Glow */}
      <div style={{ position:"absolute", top:"-50%", left:"50%", transform:"translateX(-50%)", width:600, height:400,
        background:"radial-gradient(circle, rgba(241,228,152,0.15) 0%, transparent 60%)", pointerEvents:"none" }}/>

      <div style={{ position:"relative", maxWidth:760, margin:"0 auto" }}>
        <div style={{ display:"inline-flex", alignItems:"center", gap:8, background:"rgba(255,255,255,0.12)", border:"1px solid rgba(255,255,255,0.25)",
          borderRadius:99, padding:"5px 16px", marginBottom:24,
          fontFamily:"DM Mono, monospace", fontSize:10, letterSpacing:3, textTransform:"uppercase", color:"rgba(255,255,255,0.8)" }}>
          <span style={{ width:6, height:6, borderRadius:"50%", background:"#F1E498", display:"block" }}/>
          Cada día sin digital = un cliente perdido
        </div>
        <h2 style={{ fontFamily:"Montserrat, sans-serif", fontWeight:900, fontSize:"clamp(32px,5.5vw,60px)",
          lineHeight:1.02, letterSpacing:"-2px", color:"white", marginBottom:16 }}>
          Deja de perder clientes<br/>por no estar digital
        </h2>
        <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:17, color:"rgba(255,255,255,0.75)", marginBottom:36, lineHeight:1.6 }}>
          Tu competencia ya tiene chatbot. ¿Y tú?
        </p>
        <div style={{ display:"flex", gap:12, justifyContent:"center", flexWrap:"wrap" }}>
          <a href="#contacto" style={{ background:"#F1E498", color:"#050709", fontFamily:"Montserrat, sans-serif", fontWeight:800, fontSize:15,
            padding:"15px 32px", borderRadius:12, textDecoration:"none", transition:"all .2s" }}>
            Empezar hoy →
          </a>
          <a href="#precios" style={{ border:"2px solid rgba(255,255,255,0.35)", color:"white", fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:15,
            padding:"13px 32px", borderRadius:12, textDecoration:"none", background:"transparent", transition:"all .2s" }}>
            Ver planes
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────────────────────────────────────────
   FOOTER
───────────────────────────────────────────────────────────── */
function Footer() {
  return (
    <footer style={{ background:"#050709", borderTop:"1px solid rgba(255,255,255,0.06)" }}>
      <div style={{ maxWidth:1280, margin:"0 auto", padding:"60px 28px 32px" }}>
        <div style={{ display:"grid", gridTemplateColumns:"2fr 1fr 1fr 1fr", gap:48, marginBottom:48 }}>
          <div>
            <Logo />
            <p style={{ fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.3)", lineHeight:1.7, marginTop:16, maxWidth:280 }}>
              Ecosistemas digitales completos para emprendedores y empresas venezolanas que quieren crecer sin límites.
            </p>
            <div style={{ display:"flex", gap:8, marginTop:20 }}>
              {["In","Ig","Fb","TT"].map(s=>(
                <a key={s} href="#" style={{ width:34, height:34, borderRadius:8, display:"flex", alignItems:"center", justifyContent:"center",
                  background:"rgba(255,255,255,0.04)", border:"1px solid rgba(255,255,255,0.08)",
                  fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:11, color:"rgba(255,255,255,0.4)", textDecoration:"none" }}>{s}</a>
              ))}
            </div>
          </div>
          {[
            { t:"Servicios", l:["Web Development","Chatbots IA","Automatización CRM","Estrategia Digital"] },
            { t:"Empresa",   l:["Sobre nosotros","Casos de éxito","Blog","Contacto"] },
            { t:"Legal",     l:["Privacidad","Términos de uso","Cookies"] },
          ].map(col=>(
            <div key={col.t}>
              <p style={{ fontFamily:"Montserrat, sans-serif", fontWeight:700, fontSize:13, color:"white", marginBottom:16 }}>{col.t}</p>
              {col.l.map(l=>(
                <a key={l} href="#" style={{ display:"block", fontFamily:"DM Sans, sans-serif", fontSize:13, color:"rgba(255,255,255,0.35)", textDecoration:"none", marginBottom:10, transition:"color .2s" }}
                  onMouseEnter={e=>e.currentTarget.style.color="white"} onMouseLeave={e=>e.currentTarget.style.color="rgba(255,255,255,0.35)"}>{l}</a>
              ))}
            </div>
          ))}
        </div>
        <div style={{ paddingTop:24, borderTop:"1px solid rgba(255,255,255,0.06)", display:"flex", justifyContent:"space-between", alignItems:"center", flexWrap:"wrap", gap:12 }}>
          <p style={{ fontFamily:"DM Mono, monospace", fontSize:11, color:"rgba(255,255,255,0.2)" }}>© 2025 Create Geek Agency. Todos los derechos reservados.</p>
          <p style={{ fontFamily:"DM Mono, monospace", fontSize:11, color:"rgba(255,255,255,0.15)" }}>creategeek.agency</p>
        </div>
      </div>
    </footer>
  );
}

/* ─────────────────────────────────────────────────────────────
   APP
───────────────────────────────────────────────────────────── */
export default function App() {
  return (
    <>
      <style>{`
        ${GF}
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { background: #050709; overflow-x: hidden; }
        input::placeholder, textarea::placeholder { color: rgba(255,255,255,0.2); }
        select option { background: #0f1417; color: white; }
        @media (max-width: 900px) {
          .nav-desktop { display: none !important; }
        }
        @media (max-width: 768px) {
          [style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
          [style*="grid-template-columns: 2fr 1fr 1fr 1fr"] { grid-template-columns: 1fr 1fr !important; }
          [style*="grid-template-columns: repeat(4,1fr)"] { grid-template-columns: repeat(2,1fr) !important; }
          [style*="font-size:\"clamp(40px"] { font-size: 36px !important; }
        }
      `}</style>
      <Navbar />
      <main>
        <Hero />
        <Marquee />
        <Services />
        <Features />
        <Process />
        <Testimonials />
        <Pricing />
        <Contact />
        <CTABanner />
      </main>
      <Footer />
    </>
  );
}
