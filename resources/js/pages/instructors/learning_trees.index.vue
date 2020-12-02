<template>
  <div>
    <PageTitle v-if="canViewCourses" title="My Learning Trees"></PageTitle>
    <div v-if="user.role === 2">
      <div class="row mb-4 float-right" v-if="canViewCourses">
        <b-button variant="primary" v-b-modal.modal-learning-tree-details>Add Learning Trees</b-button>
      </div>
    </div>

    <b-modal
      id="modal-learning-tree-details"
      ref="modal"
      title="Course Details"
      @ok="submitCourseInfo"
      @hidden="resetModalForms"
      ok-title="Submit"

    >
      <b-form ref="form">
        <b-form-group
          id="name"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Name"
          label-for="name"
        >
          <b-form-input
            id="name"
            v-model="form.name"
            type="text"
            :class="{ 'is-invalid': form.errors.has('name') }"
            @keydown="form.errors.clear('name')"
          >
          </b-form-input>
          <has-error :form="form" field="name"></has-error>
        </b-form-group>


      </b-form>
    </b-modal>

    <b-modal
      id="modal-delete-course"
      ref="modal"
      title="Confirm Delete Course"
      @ok="handleDeleteCourse"
      @hidden="resetModalForms"
      ok-title="Yes, delete course!"

    >
      <p>By deleting the course, you will also delete:</p>
      <ol>
        <li>All assignments associated with the course</li>
        <li>All submitted student responses</li>
        <li>All student scores</li>
      </ol>
      <p><strong>Once a course is deleted, it can not be retrieved!</strong></p>
    </b-modal>

    <div v-if="hasCourses">
      <b-table striped hover
               :fields="fields"
               :items="courses">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" v-on:click.prevent="showAssignments(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
        <template v-slot:cell(start_date)="data">
          {{ $moment(data.item.start_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(end_date)="data">
          {{ $moment(data.item.end_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <span v-if="user.role === 2">
                  <b-tooltip ref="tooltip"
                             :target="getTooltipTarget('pencil',data.item.id)"
                             delay="500"
                  >
                    Edit Learning Tree
                  </b-tooltip>
                              <span class="pr-1" v-on:click="editLearningTree(data.item)">
                  <b-icon :id="getTooltipTarget('pencil',data.item.id)" icon="pencil"></b-icon>
                </span>
              <span class="pr-1" v-on:click="inviteGrader(data.item.id)">
                       <b-tooltip :target="getTooltipTarget('inviteGrader',data.item.id)"
                                  delay="500">
                         Invite Grader
                       </b-tooltip>
                <b-icon :id="getTooltipTarget('inviteGrader',data.item.id)" icon="people">

                </b-icon>
              </span>
              <span class="pr-1" v-on:click="updateAccessCode(data.item)">
                  <b-tooltip :target="getTooltipTarget('refreshAccessCode',data.item.id)"
                             delay="500">
                    Refresh Access Code
                  </b-tooltip>
                <b-icon :id="getTooltipTarget('refreshAccessCode',data.item.id)" icon="arrow-repeat"></b-icon>
              </span>
              <b-tooltip :target="getTooltipTarget('deleteCourse',data.item.id)"
                         delay="500">
                    Delete Course
                  </b-tooltip>
                <b-icon :id="getTooltipTarget('deleteCourse',data.item.id)" icon="trash" v-on:click="deleteCourse(data.item.id)"></b-icon>
            </span>
          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoCoursesAlert" variant="warning"><a href="#" class="alert-link">You currently have no
          courses.
        </a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"
import {mapGetters} from "vuex"
import {getTooltipTarget} from '../../helpers/Tooptips'
import {initTooltips} from "../../helpers/Tooptips"

const now = new Date()
export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    fields: [
      {
        key: 'title',
        label: 'Title',
        sortable: true
      },
      {
        key: 'description',
        sortable: true
      },
      'actions'
    ],
    learningTrees: [],
    learningTreeForm: new Form({
      title: '',
      description: ''
    }),
    canViewCourses: false
  }),
  mounted() {
    this.LearningTrees()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)

  },
  methods: {
    async deleteGrader(userId) {
      try {
        const {data} = await axios.delete(`/api/grader/${this.courseId}/${userId}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error('We were not able to remove the grader from the course.  Please try again or contact us for assistance.')
          return false
        }
        this.$noty.success(data.message)
        //remove the grad
        this.graders = this.graders.filter(grader => parseFloat(grader.id) !== parseFloat(userId))


      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    deleteCourse(courseId) {
      this.courseId = courseId
      this.$bvModal.show('modal-delete-course')
    },
    async handleDeleteCourse() {
      try {
        const {data} = await axios.delete('/api/courses/' + this.courseId)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-course')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    ,
    editLearningTree(learning_tree) {
      this.$refs.tooltip.$emit('close')
      this.learningTreeId = learning_tree.id;
      this.learningTreeForm.title= learning_tree.title
      this.learningTreeForm.description = learning_tree.description
      this.$bvModal.show('modal-learning-tree-details')
    },
    resetModalForms() {
      this.form.name = ''
      this.form.start_date = ''
      this.form.end_date = ''
      this.graderForm.email = ''
      this.courseId = false
      this.form.errors.clear()
    }
    ,
    resetAll(modalId) {
      this.getCourses()
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    }
    ,
    submitCourseInfo(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      !this.courseId ? this.createCourse() : this.updateCourse()
    }
    ,
    async createLearningTree() {
      try {
        const {data} = await this.form.post('/api/courses')
        this.$noty[data.type](data.message)
        this.resetAll('modal-learning-tree-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }

    },
    async updateLearningTree() {
      try {
        const {data} = await this.form.patch(`/api/courses/${this.courseId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-learning-tree-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }

      }

    }
    ,
    async getCourses() {
      try {
        const {data} = await axios.get('/api/courses')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.canViewCourses = true
          this.hasCourses = data.courses.length > 0
          this.showNoCoursesAlert = !this.hasCourses
          this.courses = data.courses
          console.log(data.courses)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>
<style>
body, html {
  overflow: visible;

}
svg:focus, svg:active:focus {
  outline: none !important;
}
</style>
