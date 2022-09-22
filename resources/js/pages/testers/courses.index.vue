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
        <b-modal
          id="modal-course-assignments"
          :title="`${courseName} assignments`"
          size="lg"
        >
          <p>
            You can create new testing students, log in as current testing students, and view testing student results
            for any of the assignments below.
          </p>
          <ol>
            <li v-for="assignment in assignments" :key="`assignment-${assignment.id}`">
              <router-link
                :to="{name: 'testers.students.results', params: {courseId: courseId, assignmentId: assignment.id}}"
              > {{ assignment.name }}
              </router-link>
            </li>
          </ol>
          <template #modal-footer="{ cancel, ok }">
            <b-button size="sm" @click="$bvModal.hide('modal-course-assignments')">
              Cancel
            </b-button>
          </template>
        </b-modal>
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
            <router-link v-if="data.item.id === 377"
                         :to="{name: 'testers.students.results', params: {courseId: data.item.id}}"
            >
              {{ data.item.name }}
            </router-link>
            <div>
              <a v-if="data.item.id !== 377" href="" @click.prevent="showAssignmentsModal(data.item.name,data.item.id)"
              >{{ data.item.name }}</a>
            </div>
          </template>
          <template v-slot:cell(start_date)="data">
            {{ $moment(data.item.start_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          </template>
          <template v-slot:cell(end_date)="data">
            {{ $moment(data.item.end_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          </template>
        </b-table>
        <b-alert :show="!courses.length" variant="info">
          You currently have no courses. Instructors can add you as a tester for their courses.
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
    courseId: 0,
    assignments: [],
    courseName: '',
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
    async showAssignmentsModal (courseName, courseId) {
      this.courseName = courseName
      this.courseId = courseId
      try {
        const { data } = await axios.get(`/api/assignments/courses/${this.courseId}`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.assignments = data.assignments
        this.$bvModal.show('modal-course-assignments')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCourses () {
      try {
        const { data } = await axios.get('/api/courses')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.courses = data.courses
        console.log(this.courses)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }

}
</script>

