<template>
  <div>
    <PageTitle title="Courses To Reset"/>
    <ResetCourse v-if="course.id"
                 :key="course.id"
                 :course="course"
                 :course-id="course.id"
                 :show-download="false"
                 @parentReloadData="getCoursesToReset"
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
        <div v-if="coursesToReset.length">
          <p>The following courses ended at least 100 days ago and still have students enrolled in the course.</p>
          <b-table
            striped
            hover
            :no-border-collapse="true"
            :items="coursesToReset"
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
import ResetCourse from '~/components/ResetCourse'

export default {
  metaInfo () {
    return { title: this.$t('Courses To Unenroll') }
  },
  components: {
    Loading,
    ResetCourse
  },
  data: () => ({
    course: {},
    coursesToReset: [],
    isLoading: true,
    fields: [
      {
        key: 'name',
        label: 'Course'
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
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getCoursesToReset()
  },
  methods: {
    async unenrollAllStudents (course) {
      this.course = course
      this.$nextTick(() => {
          this.$bvModal.show('modal-reset-course')
        }
      )
    },
    async getCoursesToReset () {
      try {
        const { data } = await axios.get('/api/courses/to-reset/more-than/100')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.coursesToReset = data.courses_to_reset
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
