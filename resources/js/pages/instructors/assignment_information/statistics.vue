<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <PageTitle v-if="!isLoading" title="Assignment Statistics" />
      <AssignmentStatistics @loaded-statistics="loadedStatistics" />
    </div>
  </div>
</template>

<script>

import AssignmentStatistics from '../../../components/AssignmentStatistics'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  components: { AssignmentStatistics, Loading },
  metaInfo () {
    return { title: 'Assignment Statistics' }
  },
  middleware: 'auth',
  data: () => ({
    isLoading: true
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
  },
  methods: {
    loadedStatistics () {
      this.isLoading = false
    }
  }
}
</script>
