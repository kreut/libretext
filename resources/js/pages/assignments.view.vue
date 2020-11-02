<template>
  <div>
   sdfdsfsdf
    this.$router.push(`/assignments/${assignment.id}/questions/view`)
  </div>
</template>


<script>

import {mapGetters} from "vuex"

export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({

  }),

  async mounted() {


  },
  methods: {

  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>

