<template>
  <div>
    <PageTitle title="Courses To Unenroll"/>
    <UnenrollAllStudents :course="course"
                         :course-id="course.id"
                         :parent-reload-data="getCoursesToUnenroll"
    />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-show="!isLoading">
        The following courses ended at least 100 days ago and still have student enrolled in the course.
        <div v-if="coursesToUnenroll.length">
          <b-table
            striped
            hover
            :no-border-collapse="true"
            :items="coursesToUnenroll"
            :fields="fields"
          >
            <template v-slot:cell(end_date)="data">
              {{ $moment(data.item.end_date, 'YYYY-MM-DD').format('MMM DD, YYYY') }}
            </template>

            <template v-slot:cell(actions)="data">
              <a href="" @click.prevent="unenrollAllStudents(data.item)">
                <b-icon icon="trash"
                        :aria-label="`Unenroll students from  ${data.item.course}`"
                        class="text-muted"
                />
              </a>

              <a :href="`mailto:${data.item.email}?subject=${data.item.course} ended at least 100 days ago`"
                 target="_blank"
              >
                <b-icon icon="envelope"
                        :aria-label="`Contact ${data.item.instructor}`"
                        class="text-muted"
                />
              </a>
            </template>
          </b-table>
        </div>
        <div v-else>
          <b-alert :show="true" variant="info">
                    <span class="font-weight-bold">
                      There are no courses with students that are over one hundred days old.
                    </span>
          </b-alert>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import axios from 'axios'
import UnenrollAllStudents from '~/components/UnenrollAllStudents'

export default {
  metaInfo () {
    return { title: this.$t('Courses To Unenroll') }
  },
  components: {
    Loading,
    UnenrollAllStudents
  },
  data: () => ({
    course: {},
    coursesToUnenroll: [],
    isLoading: true,
    fields: [
      {
        key: 'name',
        label: 'course'
      },
      'instructor',
      'end_date',
      'actions'
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    this.getCoursesToUnenroll()
  },
  methods: {
    async unenrollAllStudents (course) {
      this.course = course
      this.$bvModal.show('modal-unenroll-all-students')
    },
    async getCoursesToUnenroll () {
      try {
        const { data } = await axios.get('/api/courses/to-unenroll')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.coursesToUnenroll = data.courses_to_unenroll
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
