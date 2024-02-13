<template>
  <div>
    <div id="counter">-</div>
  </div>
</template>

<script>

export default {
  name: 'Centrifuge',
  data: () => ({
    messages: [],
    environment: window.config.environment
  }),
  mounted () {
    const container = document.getElementById('counter')
    const centrifuge = new Centrifuge('ws://localhost:8000/connection/websocket', {
      token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM3MjIiLCJleHAiOjE3MDgzNjgyODUsImlhdCI6MTcwNzc2MzQ4NX0.T3ssWtjamgyERCmJ2LS3TQMVehXgMdhHmJDmjv4Br7s'
    })

    centrifuge.on('connecting', function (ctx) {
      console.log('connecting now')
      console.log(`connecting: ${ctx.code}, ${ctx.reason}`)
    }).on('connected', function (ctx) {
      console.log(`connected over ${ctx.transport}`)
    }).on('disconnected', function (ctx) {
      console.log(`disconnected: ${ctx.code}, ${ctx.reason}`)
    }).connect()

    const sub = centrifuge.newSubscription('channel')

    sub.on('publication', function (ctx) {
      container.innerHTML = ctx.data.value
      document.title = ctx.data.value
    }).on('subscribing', function (ctx) {
      console.log(`subscribing: ${ctx.code}, ${ctx.reason}`)
    }).on('subscribed', function (ctx) {
      console.log('subscribed', ctx)
    }).on('unsubscribed', function (ctx) {
      console.log(`unsubscribed: ${ctx.code}, ${ctx.reason}`)
    }).subscribe()
  },
  methods: {}
}
</script>

<style scoped>

</style>
