<template>
  <div>
    <div class="vld-parent">
      <PageTitle title="My Courses"/>
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <b-table
          v-if="courses.length"
          aria-label="Progress Report"
          striped
          hover
          :no-border-collapse="true"
          :items="courses"
          :fields="fields"
        >
          <template v-slot:cell(name)="data">
            <router-link :to="{name: 'testers.students.results', params: {courseId: data.item.id}}">
              {{ data.item.name }}
            </router-link>
          </template>
          <template v-slot:cell(start_date)="data">
            {{ $moment(data.item.start_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          </template>
          <template v-slot:cell(end_date)="data">
            {{ $moment(data.item.end_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          </template>
        </b-table>
        <b-alert :show="!courses.length" variant="info">
          You currently have no courses.  Instructors can add you as a tester for their courses.
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    courses: [],
    isLoading: true,
    fields: [
      {
        key: 'name',
        sortable: true
      },
      {
        key: 'instructor',
        sortable: true
      },
      {
        key: 'term',
        sortable: true
      },
      {
        key: 'start_date',
        sortable: true
      },
      {
        key: 'end_date',
        sortable: true
      }
    ]
  }),
  mounted () {
    this.getCourses()
  },
  methods: {
    async getCourses () {
      try {
        const { data } = await axios.get('/api/courses')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false

        }
        this.courses = data.courses
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }

}
</script>

<style scoped>

</style>
