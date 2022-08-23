<template>
  <div>
    <PageTitle v-if="!loading" :title="courseName"/>
    <div class="vld-parent">
      <!--Use loading instead of isLoading because there's both the assignment and scores loading-->
      <loading :active.sync="loading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="assignments.length && !loading">
        <div class="pb-2">
          <ImportCourse v-if="user && user.role === 2"
                        :open-course="openCourse"
                        :button-class="'primary'"
          />
        </div>
        <b-table
          striped
          hover
          :no-border-collapse="true"
          :items="assignments"
          :fields="fields"
        >
          <template v-slot:cell(name)="data">
            <router-link :to="{ name: 'questions.view', params: {assignmentId:data.item.id} }"
            >
              {{ data.item.name }}
            </router-link>
          </template>
          <template v-slot:cell(public_description)="data">
            {{ data.item.public_description ? data.item.public_description : 'None available' }}
          </template>
        </b-table>
      </div>
      <div v-else>
        <b-alert :show="showNoAssignmentsAlert" variant="warning">
          <a href="#" class="alert-link">This course currently
            has
            no assignments.</a>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>

import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import ImportCourse from '~/components/ImportCourse'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading,
    ImportCourse
  },
  metaInfo () {
    return { title: 'Assignments' }
  },
  middleware: 'auth',
  data: () => ({
    openCourse: {},
    courseName: '',
    loading: true,
    assignments: [],
    courseId: false,
    fields: [
      { key: 'name', isRowHeader: true },
      'number_of_questions',
      {
        key: 'public_description',
        label: 'Description'
      }
    ],
    hasAssignments: false,
    showNoAssignmentsAlert: false,
    canViewAssignments: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getOpenCourse()
    this.getAssignments()
  },
  methods: {
    async getOpenCourse () {
      try {
        const { data } = await axios.get(`/api/courses/open/${this.courseId}`)
        if (data.type !== 'success') {
          this.isLoading = false
          this.$noty[data.type](data.message)
          return false
        }
        this.openCourse = data.open_course
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async getAssignments () {
      try {
        const { data } = await axios.get(`/api/assignments/courses/${this.courseId}/anonymous-user`)
        this.loading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assignments = data.assignments
        this.courseName = data.course_name
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.loading = false
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
