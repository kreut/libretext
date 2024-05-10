<template>
  <div>
    {{ solution }}
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data: () => ({
    jwt: '',
    solution: ''
  }),
  mounted () {
    this.getSolution(this.$route.params.jwt)
  },
  methods: {
    async getSolution (jwt) {
      const { data } = await axios.get(`/api/questions/imathas-solution/${jwt}`)
      console.log(data)
    }
  }
}
</script>

<style scoped>

</style>
