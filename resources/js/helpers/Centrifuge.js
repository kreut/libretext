import axios from 'axios'

export async function initCentrifuge () {
  try {
    const { data } = await axios.get('/api/centrifugo/token')
    if (data.type === 'success') {
      const token = data.token
      const protocol = window.location.hostname === 'local.adapt' ? 'ws' : 'wss'
      const domain = data.domain
      const url = `${protocol}://${domain}/connection/websocket`
      const centrifuge = new Centrifuge(url, {
        token: token
      })
      centrifuge.on('connecting', function (ctx) {
        console.log('connecting now')
        console.log(`connecting: ${ctx.code}, ${ctx.reason}`)
      }).on('connected', function (ctx) {
        console.log(`connected over ${ctx.transport}`)
      }).on('disconnected', function (ctx) {
        console.log(`disconnected: ${ctx.code}, ${ctx.reason}`)
      }).connect()
      return centrifuge
    } else {
      alert(data.message)
    }
  } catch (error) {
    console.log(error)
    alert(error.message)
  }
}
