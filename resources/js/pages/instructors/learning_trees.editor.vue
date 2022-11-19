<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-learning-tree'"/>
    <b-modal
      id="modal-learning-tree-instructions"
      ref="modal-learning-tree-instructions"
      title="Instructions"
      size="lg"
    >
      <p>
        After creating a
        <b-button variant="success" aria-label="New Tree" class="inline-button" size="sm">
          New Tree
        </b-button>
        by providing a Title and
        Description for the Learning Tree, you can add nodes
        either using the
        <b-button size="sm" aria-label="New Node" variant="outline-secondary" class="inline-button">
          New Node
        </b-button>
        button
        to create an empty node which can then be populated with a newly created question or you can use the
        <b-button size="sm" aria-label="Add Node" variant="primary" class="inline-button">
          Add Node
        </b-button>
        button to create a node based on an existing question.
      </p>
      <p>
        To create a node based on an existing question, you can specify its contents by using the single number ADAPT ID
        found in
        "My Questions".
        Alternatively, if you already
        have a question in
        one of your assignments, you can visit that assignment and go to the Questions tab under Assignment Information
        to find the
        ADAPT ID; this ID will be of the form {number}-{number}.
      </p>
      <p>
        Next, drag the new node from the side panel to the main editing area. Each of the non-root assessment
        nodes should then be given a Branch
        Description to
        help students decide which nodes to visit as they navigate the tree. Using command+click on any of the nodes
        will open that node's editor.
      </p>
      <p>
        To remove a node, just drag it to the left of the screen. And, if you make a mistake, you can always use the
        undo icon (
        <font-awesome-icon
          aria-label="Undo"
          scale="1.1"
          :icon="undoIcon"
        />
        ).
      </p>
      <p>
        Finally, nodes are color-coded to help differentiate between the different types based on their
        contents:
      </p>
      <b-container class="pb-4">
        <b-row>
          <b-col style="width:200px">
            <div class="blockelem empty-node-border text-center pb-3">
              Empty learning tree nodes
            </div>
          </b-col>
          <b-col style="width:200px">
            <div class="blockelem exposition-border text-center pb-3">
              Exposition nodes
            </div>
          </b-col>
          <b-col>
            <div class="blockelem question-border text-center pb-3">
              Question nodes
            </div>
          </b-col>
        </b-row>
      </b-container>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-learning-tree-instructions')">
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-update-node"
      ref="modal"
      title="Update Node"
      size="xl"
      no-close-on-backdrop
      no-close-on-esc
      hide-footer
    >
      <div v-if="!showUpdateNodeContents">
        <div class="d-flex justify-content-center mb-3">
          <div class="text-center">
            <b-spinner variant="primary" label="Text Centered"/>
            <span style="font-size:30px" class="text-primary"> Loading Contents</span>
          </div>
        </div>
      </div>
      <div v-if="showUpdateNodeContents">
        <div v-if="isAuthor" class="flex d-inline-flex pb-4" style="width:100%">
          <label class="pr-2" style="width:150px">Node Title</label>
          <b-form-input
            v-model="nodeForm.title"
            size="sm"
            placeholder="Enter a node title or leave blank to use the question title"
            type="text"
          />
        </div>
        <div v-if="isAuthor" class="flex d-inline-flex pb-4" style="width:100%">
          <label class="pr-2" style="width:150px">Question Title</label>
          <b-form-input
            v-model="questionToView.title"
            size="sm"
            disabled
            placeholder="Enter a node title or leave blank to use the question title"
            type="text"
          />
        </div>
      </div>
      <ViewQuestionWithoutModal :key="`question-to-view-${questionToViewKey}`" :question-to-view="questionToView"/>
      <div v-if="showUpdateNodeContents">
        <hr>
        <b-form ref="form">
          <b-form-group>
            <div v-if="isAuthor" class="flex d-inline-flex">
              <label class="pr-2">
                <span>ADAPT ID*
                </span>
              </label>
              <b-form-input
                id="node_question_id"
                v-model="nodeForm.question_id"
                type="text"
                size="sm"
                style="width: 100px"
                :class="{ 'is-invalid': nodeForm.errors.has('question_id') }"
                @keydown="nodeForm.errors.clear('question_id')"
              />
              <has-error :form="nodeForm" field="question_id"/>
              <span class="pl-2"><b-button size="sm" variant="info" @click="editSource">
                {{ questionToView.can_edit ? 'Edit' : 'View' }} Question Source
              </b-button></span>
              <span class="pl-2">
                <b-button
                  size="sm"
                  variant="info"
                  @click="getQuestionToView(nodeForm.question_id)"
                >
                  Reload Node
                </b-button>
              </span>
              <a id="reload-question-tooltip"
                 href=""
                 @click.prevent
              >
                <b-icon-question-circle style="width: 25px; height: 25px;margin-top:4px" class="text-muted pl-2"/>
              </a>
              <b-tooltip target="reload-question-tooltip"
                         delay="250"
                         triggers="hover focus"
              >
                If you edit the current question source or you change the ADAPT ID entirely, you can reload the question
                for viewing.
              </b-tooltip>
            </div>
          </b-form-group>
          <div v-if="!isAuthor">
            <div>
              <span class="pr-2">ADAPT ID*: {{ nodeForm.question_id }}</span>
              <b-button size="sm" variant="info" @click="editSource">
                View Question Source
              </b-button>
            </div>
          </div>
          <div v-if="!isRootNode">
            <b-form-group
              v-if="isAuthor"
              label="Branch Description*"
              label-for="branch_description"
              class="mb-3"
            >
              <b-form-textarea
                id="branch_description"
                v-model="nodeForm.branch_description"
                type="text"
                :class="{ 'is-invalid': nodeForm.errors.has('branch_description') }"
                rows="3"
                @keydown="nodeForm.errors.clear('branch_description')"
              />
              <has-error :form="nodeForm" field="branch_description"/>
            </b-form-group>
            <div v-if="!isAuthor">
              Branch Description: {{ nodeForm.branch_description ? nodeForm.branch_description : 'None provided.' }}
            </div>
          </div>
          <div v-if="isAuthor">
            <b-form-group
              label="Notes"
              label-for="notes"
              class="mb-3"
            >
              <b-form-textarea
                id="branch_description"
                v-model="nodeForm.notes"
                type="text"
                rows="3"
              />
            </b-form-group>
          </div>
        </b-form>
        <div class="float-right">
          <b-button size="sm" @click="$bvModal.hide('modal-update-node')">
            Cancel
          </b-button>
          <span v-if="isAuthor">
            <b-button size="sm"
                      variant="primary"
                      :disabled="isUpdating"
                      @click="submitUpdateNode"
            >
              <span v-if="!isUpdating">Save</span>
              <span v-if="isUpdating"><b-spinner small type="grow"/> Updating...</span>
            </b-button>
          </span>
        </div>
      </div>
    </b-modal>

    <b-modal
      id="modal-learning-tree-properties"
      ref="modal"
      title="Properties"
      no-close-on-backdrop
      @hidden="resetLearningTreePropertiesModal"
    >
      <RequiredText/>

      <b-form ref="form">
        <b-form-group v-if="learningTreeId">
          <label for="learningTreeId" class="col-sm-5 col-lg-4 col-form-label pl-0">
            Learning Tree ID
          </label><span id="learningTreeId">{{ learningTreeId }}</span>
        </b-form-group>

        <b-form-group
          label-cols-sm="5"
          label-cols-lg="4"
          label-for="learning_tree_title"
        >
          <template v-slot:label>
            Title*
          </template>
          <b-form-input
            id="learning_tree_title"
            v-model="learningTreeForm.title"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('title') }"
            @keydown="learningTreeForm.errors.clear('title')"
          />
          <has-error :form="learningTreeForm" field="title"/>
        </b-form-group>

        <b-form-group
          label-cols-sm="5"
          label-cols-lg="4"
          label-for="description"
        >
          <template v-slot:label>
            Description*
          </template>
          <b-form-textarea
            id="description"
            v-model="learningTreeForm.description"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('description') }"
            @keydown="learningTreeForm.errors.clear('description')"
          />
          <has-error :form="learningTreeForm" field="description"/>
        </b-form-group>
      </b-form>
      <b-form-group
        label-cols-sm="5"
        label-cols-lg="4"
        label-for="public"
      >
        <template v-slot:label>
          Public*
          <QuestionCircleTooltip id="public-learning-tree-tooltip"/>
          <b-tooltip target="public-learning-tree-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            Learning trees that are public can be used by any instructor. Learning trees that are not public are only
            accessible
            by you.
          </b-tooltip>
        </template>
        <b-form-row class="mt-2">
          <b-form-radio-group
            id="public"
            v-model="learningTreeForm.public"
          >
            <b-form-radio name="public" value="1">
              Yes
            </b-form-radio>
            <b-form-radio name="public" value="0">
              No
            </b-form-radio>
          </b-form-radio-group>
        </b-form-row>
      </b-form-group>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm"
                  @click="$bvModal.hide('modal-learning-tree-properties');resetLearningTreePropertiesModal"
        >
          Cancel
        </b-button>
        <b-button size="sm"
                  variant="primary"
                  @click="submitLearningTreeInfo"
        >
          Submit
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-delete-learning-tree"
      ref="modal"
      title="Confirm Delete Learning Tree"
    >
      <p>Please note that once a Learning Tree is deleted, it can not be retrieved.</p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-delete-learning-tree')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="handleDeleteLearningTree"
        >
          Yes, delete learning tree!
        </b-button>
      </template>
    </b-modal>
    <div v-if="isAuthor" style="margin-left:-100px;">
      <span class="pr-4">
        <b-button size="sm"
                  variant="outline-info"
                  @click="$bvModal.show('modal-learning-tree-instructions')"
        >
          Instructions
        </b-button>
      </span>
      <toggle-button
        v-if="(user !== null)"
        tabindex="0"
        class="mt-2"
        :width="170"
        :value="!isLearningTreeView"
        :sync="true"
        :font-size="14"
        :margin="4"
        :color="{checked: '#6c757d', unchecked: '#28a745'}"
        :labels="{checked: 'Editor/Learning Tree', unchecked: 'Learning Tree Only'}"
        @change="toggleLearningTreeView()"
      />
    </div>
    <div v-if="user.role === 2 && !isLearningTreeView && isAuthor" id="leftcard">
      <div id="actions">
        <b-button variant="success" size="sm" class="mr-2" @click="initCreateNew">
          New Tree
        </b-button>

        <b-icon id="properties-tooltip"
                icon="gear"
                :class="{ 'disabled': learningTreeId === 0}"
                :aria-disabled="learningTreeId === 0"
                scale="1.1"
                class="mr-2"
                @click="learningTreeId === 0 ? '' : editLearningTree()"
        />
        <b-tooltip target="properties-tooltip"
                   delay="250"
                   triggers="hover"
        >
          Edit properties
        </b-tooltip>
        <b-icon-trash id="delete-tree-tooltip"
                      :class="{ 'disabled': learningTreeId === 0}"
                      :aria-disabled="learningTreeId === 0"
                      scale="1.1"
                      class="mr-2"
                      @click="learningTreeId === 0 ? '' : deleteLearningTree()"
        />

        <b-tooltip target="delete-tree-tooltip"
                   delay="250"
                   triggers="hover"
        >
          Delete the current learning tree
        </b-tooltip>

        <font-awesome-icon id="undo-action-tooltip"
                           :class="{ 'disabled': !canUndo}"
                           aria-label="Undo"
                           class="mr-2"
                           scale="1.1"
                           :icon="undoIcon"
                           @click="!canUndo ? '' : undo()"
        />
        <b-tooltip target="undo-action-tooltip"
                   delay="250"
                   triggers="hover"
        >
          Undo the last action
        </b-tooltip>
        <b-button :class="{ 'disabled': learningTreeId === 0}"
                  class="ml-2 mr-2"
                  :aria-disabled="learningTreeId === 0"
                  :disabled="learningTreeId === 0"
                  variant="outline-secondary"
                  size="sm"
                  @click="addRemediation"
        >
          <b-spinner v-if="validatingQuestionId" small label="Spinning"/>
          New Node
        </b-button>
        <div id="search" class="pt-2">
          <div class="d-flex flex-row">
            <b-form-input v-model="questionId" style="width:175px;"
                          size="sm"
                          placeholder="ADAPT ID"
            />
            <b-button :class="{ 'disabled': learningTreeId === 0}"
                      class="ml-2 mr-2"
                      :disabled="learningTreeId === 0"
                      :aria-disabled="learningTreeId === 0"
                      variant="primary"
                      size="sm"
                      @click="addRemediation"
            >
              <b-spinner v-if="validatingQuestionId" small label="Spinning"/>
              Add Node
            </b-button>
          </div>
        </div>
      </div>
      <div id="blocklist"/>
    </div>

    <div id="canvas" :class="isLearningTreeView ? 'learningTreeView' : 'learningTreeAndEditorView'"/>
  </div>
</template>

<script>

import { flowy } from '~/helpers/Flowy'
import $ from 'jquery'
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUndo } from '@fortawesome/free-solid-svg-icons'
import AllFormErrors from '~/components/AllFormErrors'
import { ToggleButton } from 'vue-js-toggle-button'
import ViewQuestionWithoutModal from '~/components/ViewQuestionWithoutModal'
import { h5pResizer } from '~/helpers/H5PResizer'
import 'vue-select/dist/vue-select.css'
import { getLearningOutcomes, subjectOptions } from '~/helpers/LearningOutcomes'

window.onmousemove = function (e) {
  window.doNotDrag = e.ctrlKey || e.metaKey
}
export default {

  metaInfo () {
    return { title: 'Learning Trees Editor' }
  },
  components: {
    FontAwesomeIcon,
    ToggleButton,
    AllFormErrors,
    ViewQuestionWithoutModal
  },
  data: () => ({
    questionId: '',
    isAuthor: false,
    fromAllLearningTrees: 0,
    learningOutcome: '',
    subject: null,
    subjectOptions: subjectOptions,
    learningOutcomeOptions: [],
    isUpdating: false,
    isRootNode: false,
    questionToViewKey: 0,
    showUpdateNodeContents: false,
    questionToView: {},
    allFormErrors: [],
    isLearningTreeView: false,
    nodeSrc: '',
    nodeIframeId: '',
    canUndo: false,
    undoIcon: faUndo,
    nodeForm: new Form({
      question_id: '',
      title: '',
      notes: '',
      branch_description: '',
      learning_outcome_description: ''
    }),
    nodeToUpdate: {},
    learningTreeForm: new Form({
      title: '',
      description: '',
      public: 0,
      question_id: ''
    }),
    assessmentQuestionId: '',
    touchingBlock: false,
    validatingQuestionId: false,
    panelHidden: false,
    studentLearningObjectives: '',
    title: window.config.appName,
    chosenId: '',
    learningTreeId: 0
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    h5pResizer()
    this.getLearningOutcomes = getLearningOutcomes
    window.addEventListener('keydown', this.hotKeys)
  },
  destroyed () {
    window.removeEventListener('keydown', this.hotKeys)
  },
  async mounted () {
    if (this.user.role !== 2) {
      await this.$router.push({ name: 'no.access' })
      return false
    }
    let tempblock
    let tempblock2
    console.log(document.getElementById('canvas'))

    flowy(document.getElementById('canvas'), drag, release, snapping, rearranging, 20, 30)

    function addEventListenerMulti (type, listener, capture, selector) {
      let nodes = document.querySelectorAll(selector)
      for (let i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener(type, listener, capture)
      }
    }

    function rearranging (block, parent) {
      // Needed so that I could redefine the y distance in flowy
    }

    function snapping (drag, first) {
      let grab = drag.querySelector('.grabme')
      grab.parentNode.removeChild(grab)

      let blockin = drag.querySelector('.blockin')

      blockin.parentNode.removeChild(blockin)
      let isAssessmentNode = (drag.querySelector('.blockelemtype').value === '1')

      // let title = isAssessmentNode ? 'Assessment' : 'Remediation'

      //  let questionId = isAssessmentNode ? '' : blockin.querySelector('.question-id').innerHTML
      let title = blockin.querySelector('.title').innerHTML
      let blockynameContents = blockin.querySelector('.blockyname').innerHTML
      let body = isAssessmentNode ? 'The original question'
        : `${blockynameContents}
<span class="extra"></span></div>`
      drag.innerHTML += `
<span class="blockyname" style="margin-bottom:0;">${body}</span>
<div class="blockyinfo">${title}
</div>`
      return true
    }

    function drag (block) {
      block.classList.add('blockdisabled')
      tempblock2 = block
    }

    function release () {
      if (tempblock2) {
        // if it's reloading a saved learning tree, this won't exist
        tempblock2.classList.remove('blockdisabled')
      }
    }

    let aclick = false
    let noinfo = false

    let vm = this
    let beginTouch = function (event) {
      aclick = true
      noinfo = false
      vm.touchingBlock = event.target.closest('#canvas') || event.target.closest('#blocklist')
      if (event.target.closest('.create-flowy')) {
        noinfo = true
      }
    }
    let checkTouch = function (event) {
      aclick = false
    }

    let doneTouch = function (event) {
      // console.log(event.target.className)
      // console.log(event.type)
      if (vm.touchingBlock) {
        vm.saveLearningTree()
      }
      if (event.type === 'mouseup' && aclick && !noinfo) {
        if (event.target.closest('.block') && !event.target.closest('.block').classList.contains('dragging')) {
          // alert(event.target.closest('.block') && !event.target.closest('.block').classList.contains('dragging'))
          vm.openUpdateNodeModal(event.target.closest('.block'))
          console.log(event.target.closest('.block').classList.contains('dragging'))
          tempblock = event.target.closest('.block')
          document.getElementById('properties').classList.add('expanded')
          tempblock.classList.add('selectedblock')
        }
      }
    }
    let openUpdateNodeModal = function (event) {
      vm.openUpdateNodeModal(event.target.closest('.block'))
      console.log('double click')
    }
    addEventListener('dblclick', openUpdateNodeModal, false)
    addEventListener('mousedown', beginTouch, false)
    addEventListener('mousemove', checkTouch, false)
    addEventListener('mouseup', doneTouch, false)
    addEventListenerMulti('touchstart', beginTouch, false, '.block')
    this.learningTreeId = parseInt(this.$route.params.learningTreeId)
    this.fromAllLearningTrees = this.$route.params.fromAllLearningTrees
    if (this.learningTreeId === 0) {
      this.isAuthor = true
      this.$bvModal.show('modal-learning-tree-properties')
    } else {
      await this.getLearningTreeLearningTreeId(this.learningTreeId)
      let questionIds = this.getQuestionIdsFromNodes()
      let questionTypes = await this.getQuestionTypes(questionIds)
      console.log(questionTypes)
      this.updateBorders(questionTypes)
    }
  },
  beforeRouteLeave (to, from, next) {
    if (to.name === 'instructors.learning_trees.index') {
      next(false)
      location.replace('/instructors/learning-trees')
    } else {
      next()
    }
  },
  methods: {
    hotKeys (event) {
      if (event.ctrlKey && event.key === 'S' && $('#modal-update-node').length) {
        this.submitUpdateNode()
      }
    },
    async getQuestionTypes (questionIds) {
      try {
        const { data } = await axios.post('/api/questions/question-types', { question_ids: questionIds })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.question_types
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    setBranchDescriptionLearningOutcome (learningOutcome) {
      if (!this.nodeForm.branch_description) {
        this.nodeForm.branch_description = learningOutcome
      }
    },
    async validateAssignmentAndQuestionId (assignmentQuestionId, isRootNode) {
      if (assignmentQuestionId === '') {
        assignmentQuestionId = 0
      }
      try {
        const { data } = await axios.get(`/api/learning-trees/validate-remediation-by-assignment-question-id/${assignmentQuestionId}/${Number(isRootNode)}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.question
      } catch (error) {
        this.$noty.error(error.message)
      }
      return false
    },
    editSource () {
      this.questionToView.id = this.nodeForm.question_id.split('-').pop()
      let url
      url = `/empty-learning-tree-node/edit/${this.questionToView.id}`
      window.open(url, '_blank')
    },
    toggleLearningTreeView () {
      this.$emit('toggle-learning-tree-view', this.isLearningTreeView)
      this.isLearningTreeView = !this.isLearningTreeView
    },
    async undo () {
      try {
        const { data } = await axios.patch(`/api/learning-tree-histories/${this.learningTreeId}`)
        console.log(data)
        if (data.type === 'success') {
          window.location.href = `/instructors/learning-trees/editor/${this.learningTreeId}`
        } else {
          this.$noty[data.type](data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async openUpdateNodeModal (nodeToUpdate) {
      if (!nodeToUpdate) {
        return false
      }
      this.isUpdating = false
      this.nodeForm.errors.clear()
      this.showUpdateNodeContents = false
      this.questionToView = {}
      this.nodeToUpdate = nodeToUpdate.closest('.block')

      let questionId = this.nodeToUpdate.querySelector('input[name="question_id"]').value
      this.isRootNode = parseInt(this.nodeToUpdate.querySelector('input[name="blockid"]').value) === 0
      this.nodeForm.is_root_node = this.isRootNode
      this.$bvModal.show('modal-update-node')
      this.nodeForm.original_question_id = questionId
      this.nodeForm.question_id = questionId
      await this.getQuestionToView(questionId)
      await this.getNodeMetaInformation(questionId)
      this.showUpdateNodeContents = true
      this.nodeIframeId = `remediation-${questionId}`
    },
    async getQuestionToView (questionId) {
      questionId = questionId.split('-').pop()
      try {
        const { data } = await axios.get(`/api/questions/${questionId}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.questionToView = data.question
        this.questionToViewKey++
      } catch (error) {
        if (error.message.includes('404')) {
          this.$noty.error(`There is no question with the ADAPT ID ${questionId}.`)
        } else {
          this.$noty.error(error.message)
        }
      }
    },
    async getNodeMetaInformation (questionId) {
      try {
        const { data } = await axios.get(`/api/learning-tree-node/meta-info/${this.learningTreeId}/${questionId}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.nodeForm.branch_description = data.description
        this.nodeForm.title = data.title
        this.nodeForm.notes = data.notes
        this.subject = data.subject
        if (data.learning_outcome) {
          if (data.subject && data.subject !== this.subject) {
            await this.getLearningOutcomes(data.subject)
          }
          this.learningOutcome = data.learning_outcome
        } else {
          await this.getLearningOutcomes(data.subject)
        }
        this.nodeForm.learning_outcome = data.learning_outcome
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitUpdateNode () {
      this.isUpdating = true
      this.nodeForm.question_id = this.nodeForm.question_id.split('-').pop()
      this.nodeForm.learning_outcome = this.learningOutcome ? this.learningOutcome.id : ''
      try {
        const { data } = await this.nodeForm.patch(`/api/learning-trees/nodes/${this.learningTreeId}`)
        console.log(data)
        if (data.type === 'success') {
          this.nodeToUpdate.querySelector('input[name="question_id"]').value = this.nodeForm.question_id
          this.nodeToUpdate.querySelector('.blockyinfo').innerHTML = data.title
          this.nodeToUpdate.querySelector('.blockyname').innerHTML = this.getBlockyNameHTML(this.nodeForm.question_id)
          await this.saveLearningTree(this.nodeForm.question_id)
        } else {
          this.$noty.error(data.message, { timeout: 20000 })
        }
        this.$bvModal.hide('modal-update-node')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.nodeForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-learning-tree')
        }
        this.isUpdating = false
      }
    },
    resetAll () {
      this.learningTreeId = 0
      document.getElementById('canvas').innerHTML = ''
      document.getElementById('blocklist').innerHTML = ''
      this.learningTreeForm.question_id = ''
    },
    initCreateNew () {
      location.replace('/instructors/learning-trees/editor/0')
    },
    deleteLearningTree () {
      this.$bvModal.show('modal-delete-learning-tree')
    },
    async handleDeleteLearningTree () {
      try {
        const { data } = await axios.delete(`/api/learning-trees/${this.learningTreeId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'info') {
          this.resetAll()
        }
        this.$bvModal.hide('modal-delete-learning-tree')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    editLearningTree () {
      this.learningTreeForm.title = this.title
      this.learningTreeForm.description = this.description
      this.learningTreeForm.public = this.public
      this.$bvModal.show('modal-learning-tree-properties')
    },
    resetLearningTreePropertiesModal () {
      this.learningTreeForm.title = ''
      this.learningTreeForm.description = ''
      this.learningTreeForm.errors.clear()
    },
    resetLearningTreeModal (modalId) {
      this.resetLearningTreePropertiesModal()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    submitLearningTreeInfo (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      !this.learningTreeId ? this.createLearningTree() : this.updateLearningTreeInfo()
    },
    async createLearningTree () {
      try {
        const { data } = await this.learningTreeForm.post('/api/learning-trees/info')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.learningTreeId = data.learning_tree_id
          this.title = this.learningTreeForm.title
          this.description = this.learningTreeForm.description
          this.public = this.learningTreeForm.public
          this.assessmentQuestionId = this.learningTreeForm.question_id
          this.$bvModal.hide('modal-learning-tree-properties')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.learningTreeForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-learning-tree')
        }
      }
    },
    async updateLearningTreeInfo () {
      try {
        const { data } = await this.learningTreeForm.post(`/api/learning-trees/info/${this.learningTreeId}`)
        this.$noty[data.type](data.message)
        this.title = this.learningTreeForm.title
        this.description = this.learningTreeForm.description
        this.public = this.learningTreeForm.public
        this.resetLearningTreeModal('modal-learning-tree-properties')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.learningTreeForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-learning-tree')
        }
      }
    },
    async getLearningTreeLearningTreeId (learningTreeId) {
      try {
        const { data } = await axios.get(`/api/learning-trees/${learningTreeId}`)

        this.title = data.title
        this.description = data.description
        this.public = data.public
        this.assessmentQuestionId = data.question_id
        this.canUndo = data.can_undo
        this.isAuthor = data.author_id === this.user.id
        if (!this.isAuthor) {
          this.isLearningTreeView = true
        }
        if (data.learning_tree) {
          let learningTree = data.learning_tree.replaceAll('/assets/img', this.asset('assets/img'))
          flowy.import(JSON.parse(learningTree))
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateBorders (questionTypes) {
      $('input[name="question_id"]').each(function () {
        let questionId = parseInt($(this).val())
        let classToAdd
        switch (questionTypes[questionId]) {
          case ('assessment'):
            classToAdd = 'question-border'
            break
          case ('exposition'):
            classToAdd = 'exposition-border'
            break
          default:
            classToAdd = 'empty-node-border'
        }
        let div = $(this).parent('div')
        div.removeClass('question-border exposition-border empty-node-border').addClass(classToAdd)
      })
    },
    async saveLearningTree () {
      if (!this.isAuthor) {
        return false
      }
      try {
        let questionIds = this.getQuestionIdsFromNodes()
        let learningTree = JSON.stringify(flowy.output()).replaceAll(this.asset('assets/img'), '/assets/img')
        const { data } = await axios.patch(`/api/learning-trees/${this.learningTreeId}`, {
          'learning_tree': learningTree,
          question_ids: questionIds
        })
        if (data.type === 'no_change') {
          return false
        }
        this.updateBorders(data.question_types)
        if (data.type === 'success') {
          document.getElementById('blocklist').innerHTML = ''
          this.questionId = ''
        }
        this.$noty[data.type](data.message)
        this.canUndo = data.can_undo
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getQuestionIdsFromNodes () {
      let html = $.parseHTML(flowy.output().html)
      let questionIds = []
      $(html).find('.question_id').each(function () {
        questionIds.push(parseInt($(this).text()))
      })
      return questionIds
    },
    getBlockyNameHTML (questionId) {
      return `<span class="question_id">${questionId}</span>`
    },
    async addRemediation () {
      let isRootNode = typeof flowy.output() === 'undefined'
      let title = ''
      let question
      question = await this.validateAssignmentAndQuestionId(this.questionId, isRootNode)
      console.log(question)
      if (question) {
        title = question.title ? question.title : 'None'
        this.questionId = question.id
      }
      if (!title) {
        return false
      }
      let blockElems = document.querySelectorAll('div.blockelem.create-flowy.noselect')
      let blockyNameHTML = this.getBlockyNameHTML(this.questionId)
      let borderClass
      borderClass = 'empty-node-border'
      if (question && !question.empty_learning_tree_node) {
        borderClass = question.question_type === 'assessment' ? 'question-border' : 'exposition-border'
      }
      let newBlockElem = `<div class="blockelem create-flowy noselect ${borderClass}">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="${blockElems.length + 2}">
        <input type="hidden" name="question_id" value="${this.questionId}">

<div class="grabme">
</div>
      <div class="blockin">
          <span class="blockyname"> ${blockyNameHTML} </span>
          <div class="blockin-info">
          <span class="blockdesc"><span class="title">${title}</span>
          <span class="extra"></span>
        </div>
        </div>
    </div>`
      if (blockElems.length > 0) {
        let lastBlockElem = blockElems[blockElems.length - 1]
        lastBlockElem.insertAdjacentHTML('afterend', newBlockElem)
      } else {
        document.getElementById('blocklist').innerHTML += newBlockElem
      }
      this.questionId = ''
    }
  }
}
</script>
<style>
.blockyinfo span.remediation-info {
  border-bottom: none;
  color: #808292;
}

/* cyrillic-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu72xKKTU1Kvnz.woff2) format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}

/* cyrillic */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu5mxKKTU1Kvnz.woff2) format('woff2');
  unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}

/* greek-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu7mxKKTU1Kvnz.woff2) format('woff2');
  unicode-range: U+1F00-1FFF;
}

/* greek */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu4WxKKTU1Kvnz.woff2) format('woff2');
  unicode-range: U+0370-03FF;
}

/* vietnamese */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu7WxKKTU1Kvnz.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+1EA0-1EF9, U+20AB;
}

/* latin-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu7GxKKTU1Kvnz.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}

/* latin */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu4mxKKTU1Kg.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}

/* cyrillic-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fCRc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}

/* cyrillic */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fABc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}

/* greek-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fCBc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+1F00-1FFF;
}

/* greek */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fBxc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0370-03FF;
}

/* vietnamese */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fCxc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+1EA0-1EF9, U+20AB;
}

/* latin-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fChc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}

/* latin */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmEU9fBBc4AMP6lQ.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}

/* cyrillic-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfCRc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}

/* cyrillic */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfABc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}

/* greek-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfCBc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+1F00-1FFF;
}

/* greek */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfBxc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0370-03FF;
}

/* vietnamese */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfCxc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+1EA0-1EF9, U+20AB;
}

/* latin-ext */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfChc4AMP6lbBP.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}

/* latin */
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfBBc4AMP6lQ.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}

body, html {
  margin: 0px;
  padding: 0px;
  overflow: hidden;
  background-repeat: repeat;
  background-size: 30px 30px;
}

#navigation {
  height: 71px;
  background-color: #FFF;
  border: 1px solid #E8E8EF;
  width: 100%;
  display: table;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  z-index: 9
}

#back {
  width: 40px;
  height: 40px;
  border-radius: 100px;
  background-color: #F1F4FC;
  text-align: center;
  display: inline-block;
  vertical-align: top;
  margin-top: 12px;
  margin-right: 10px
}

#back img {
  margin-top: 13px;
}

#names {
  display: inline-block;
  vertical-align: top;
}

#title {
  font-family: Roboto;
  font-weight: 500;
  font-size: 16px;
  color: #393C44;
  margin-bottom: 0px;
}

#subtitle {
  font-family: Roboto;
  color: #808292;
  font-size: 14px;
  margin-top: 5px;
}

#leftside {
  display: inline-block;
  vertical-align: middle;
  margin-left: 20px;
}

#centerswitch {
  position: absolute;
  width: 222px;
  left: 50%;
  margin-left: -111px;
  top: 15px;
}

#leftswitch {
  border: 1px solid #E8E8EF;
  background-color: #FBFBFB;
  width: 111px;
  height: 39px;
  line-height: 39px;
  border-radius: 5px 0px 0px 5px;
  font-family: Roboto;
  color: #393C44;
  display: inline-block;
  font-size: 14px;
  text-align: center;
}

#rightswitch {
  font-family: Roboto;
  color: #808292;
  border-radius: 0px 5px 5px 0px;
  border: 1px solid #E8E8EF;
  height: 39px;
  width: 102px;
  display: inline-block;
  font-size: 14px;
  line-height: 39px;
  text-align: center;
  margin-left: -5px;
}

#discard {
  font-family: Roboto;
  font-weight: 500;
  font-size: 14px;
  color: #A6A6B3;
  width: 95px;
  height: 38px;
  border: 1px solid #E8E8EF;
  border-radius: 5px;
  text-align: center;
  line-height: 38px;
  display: inline-block;
  vertical-align: top;
  transition: all .2s cubic-bezier(.05, .03, .35, 1);
}

#discard:hover {
  cursor: pointer;
  opacity: .7;
}

#publish {
  font-family: Roboto;
  font-weight: 500;
  font-size: 14px;
  color: #FFF;
  background-color: #217CE8;
  border-radius: 5px;
  width: 143px;
  height: 38px;
  margin-left: 10px;
  display: inline-block;
  vertical-align: top;
  text-align: center;
  line-height: 38px;
  margin-right: 20px;
  transition: all .2s cubic-bezier(.05, .03, .35, 1);
}

#publish:hover {
  cursor: pointer;
  opacity: .7;
}

#buttonsright {
  float: right;
  margin-top: 15px;
}

#get-more-assignment-questions {
  width: 300px;
  text-align: center;
  margin-left: -100px;
  margin-top: -10px;
  margin-bottom: 5px;
}

#leftcard {
  width: 300px;
  background-color: #F8F8F8;
  border: 1px solid #E8E8EF;
  box-sizing: border-box;
  padding-top: 15px;
  padding-left: 20px;
  height: 500px;
  margin-left: -100px;
  position: absolute;
  z-index: 2;
}

::-webkit-input-placeholder { /* Edge */
  color: #C9C9D5;
}

:-ms-input-placeholder { /* Internet Explorer 10-11 */
  color: #C9C9D5
}

::placeholder {
  color: #C9C9D5;
}

#header {
  font-size: 20px;
  font-family: Roboto;
  font-weight: bold;
  color: #393C44;
}

#subnav {
  border-bottom: 1px solid #E8E8EF;
  width: calc(100% + 20px);
  margin-left: -20px;
  margin-top: 10px;
}

.navdisabled {
  transition: all .3s cubic-bezier(.05, .03, .35, 1);
}

.navdisabled:hover {
  cursor: pointer;
  opacity: .5;
}

.navactive {
  color: #393C44 !important;
}

.blockelem:first-child {
  margin-top: 20px
}

.blockelem {
  padding-top: 10px;
  margin-bottom: 5px;
  width: 242px;
  border: 1px solid transparent;
  transition-property: box-shadow, height;
  transition-duration: .2s;
  transition-timing-function: cubic-bezier(.05, .03, .35, 1);
  border-radius: 5px;
  box-shadow: 0px 0px 30px rgba(22, 33, 74, 0);
  box-sizing: border-box;
  background: #FFFFFF;
}

.blockelem:hover {
  box-shadow: 0px 4px 30px rgba(22, 33, 74, 0.08);
  border-radius: 5px;
  background-color: #FFF;
  cursor: pointer;
}

#blocklist {
  height: calc(100% - 220px);
  overflow-y: auto;
}

#proplist {
  height: calc(100% - 305px);
  overflow: auto;
  margin-top: -30px;
  padding-top: 30px;
}

.blocktext {
  display: inline-block;
  width: 220px;
  vertical-align: top;
  margin-left: 12px
}

.blocktitle {
  margin: 0px !important;
  padding: 0px !important;
  font-family: Roboto;
  font-weight: 500;
  font-size: 16px;
  color: #393C44;
}

.blockdesc, .blockyinfo, .blockin-info {
  font-family: Roboto;
  color: #808292;
  font-size: 14px;
  line-height: 21px;
}

.blockyinfo, .blockin-info {
  height: 60px;
  margin-bottom: 10px;
  margin-left: 10px;
  margin-right: 10px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 3; /* number of lines to show */
  line-clamp: 3;
  -webkit-box-orient: vertical;
}

.blockdisabled {
  background-color: #F0F2F9;
  opacity: .5;
}

/*
#canvas {
  position: absolute;
  width: calc(100% - 361px);
  height: 900px;
  top: 0px;
  left: 341px;
  z-index: 0;
  overflow: auto;
}*/

.learningTreeAndEditorView {
  position: absolute;
  width: calc(100% - 361px);
  height: 900px;
  top: 0px;
  left: 341px;
  z-index: 0;
  overflow: auto;
}

.learningTreeView {
  position: absolute;
  width: calc(100% - 361px);
  height: 900px;
  top: 0px;
  left: 341px;
  z-index: 500;
}

#properties {
  position: absolute;
  height: 100%;
  width: 311px;
  background-color: #FFF;
  right: -150px;
  opacity: 0;
  z-index: 2;
  top: 0;
  box-shadow: -4px 0px 40px rgba(26, 26, 73, 0);
  padding-left: 20px;
  transition: all .25s cubic-bezier(.05, .03, .35, 1);
}

.itson {
  z-index: 2 !important;
}

.expanded {
  right: 0 !important;
  opacity: 1 !important;
  box-shadow: -4px 0px 40px rgba(26, 26, 73, 0.05);
  z-index: 2;
}

#header2 {
  font-size: 20px;
  font-family: Roboto;
  font-weight: bold;
  color: #393C44;
  margin-top: 101px;
}

#propswitch {
  border-bottom: 1px solid #E8E8EF;
  width: 331px;
  margin-top: 10px;
  margin-left: -20px;
  margin-bottom: 30px;
}

#dataprop {
  font-family: Roboto;
  font-weight: 500;
  font-size: 14px;
  text-align: center;
  color: #393C44;
  width: calc(88% / 3);
  height: 48px;
  line-height: 48px;
  display: inline-block;
  float: left;
  margin-left: 20px;
}

#dataprop:after {
  display: block;
  content: "";
  width: 100%;
  height: 4px;
  background-color: #217CE8;
  margin-top: -4px;
}

#alertprop {
  display: inline-block;
  font-family: Roboto;
  font-weight: 500;
  color: #808292;
  font-size: 14px;
  height: 48px;
  line-height: 48px;
  width: calc(88% / 3);
  text-align: center;
  float: left;
}

#logsprop {
  width: calc(88% / 3);
  display: inline-block;
  font-family: Roboto;
  font-weight: 500;
  color: #808292;
  font-size: 14px;
  height: 48px;
  line-height: 48px;
  text-align: center;
}

.inputlabel {
  font-family: Roboto;
  font-size: 14px;
  color: #253134;
}

.dropme {
  background-color: #FFF;
  border-radius: 5px;
  border: 1px solid #E8E8EF;
  box-shadow: 0px 2px 8px rgba(34, 34, 87, 0.05);
  font-family: Roboto;
  font-size: 14px;
  color: #253134;
  text-indent: 20px;
  height: 40px;
  line-height: 40px;
  width: 287px;
  margin-bottom: 25px;
}

.dropme img {
  margin-top: 17px;
  float: right;
  margin-right: 15px;
}

.checkus {
  margin-bottom: 10px;
}

.checkus img {
  display: inline-block;
  vertical-align: middle;
}

.checkus p {
  display: inline-block;
  font-family: Roboto;
  font-size: 14px;
  vertical-align: middle;
  margin-left: 10px;
}

#divisionthing {
  height: 1px;
  width: 100%;
  background-color: #E8E8EF;
  position: absolute;
  right: 0;
  bottom: 80px;
}

#removeblock {
  border-radius: 5px;
  position: absolute;
  bottom: 20px;
  font-family: Roboto;
  font-size: 14px;
  text-align: center;
  width: 287px;
  height: 38px;
  line-height: 38px;
  color: #253134;
  border: 1px solid #E8E8EF;
  transition: all .3s cubic-bezier(.05, .03, .35, 1);
}

#removeblock:hover {
  cursor: pointer;
  opacity: .5;
}

.noselect {
  -webkit-touch-callout: none; /* iOS Safari */
  -webkit-user-select: none; /* Safari */
  -khtml-user-select: none; /* Konqueror HTML */
  -moz-user-select: none; /* Old versions of Firefox */
  -ms-user-select: none; /* Internet Explorer/Edge */
  user-select: none;
  /* Non-prefixed version, currently
                                  supported by Chrome, Opera and Firefox */
}

.blockyname {
  display: none;
}

.blockyright {
  display: inline-block;
  float: right;
  vertical-align: middle;
  margin-right: 20px;
  margin-top: 10px;
  width: 28px;
  height: 28px;
  border-radius: 5px;
  text-align: center;
  background-color: #FFF;
  transition: all .3s cubic-bezier(.05, .03, .35, 1);
  z-index: 10;
}

.blockyright:hover {
  background-color: #F1F4FC;
  cursor: pointer;
}

.blockyright img {
  margin-top: 12px;
}

.block {
  background-color: #FFF;
  margin-top: 0px !important;
  box-shadow: 0px 4px 30px rgba(22, 33, 74, 0.05);
}

.selectedblock {
  border: 2px solid #217CE8;
  box-shadow: 0px 4px 30px rgba(22, 33, 74, 0.08);
}

@media only screen and (max-width: 832px) {
  #centerswitch {
    display: none;
  }
}

@media only screen and (max-width: 560px) {
  #names {
    display: none;
  }
}

.dragging {
  z-index: 111 !important
}

.block {
  position: absolute;
  z-index: 9
}

.indicator {
  width: 12px;
  height: 12px;
  border-radius: 60px;
  background-color: #217ce8;
  margin-top: -5px;
  opacity: 1;
  transition: all .3s cubic-bezier(.05, .03, .35, 1);
  transform: scale(1);
  position: absolute;
  z-index: 2
}

.invisible {
  opacity: 0 !important;
  transform: scale(0)
}

.indicator:after {
  content: "";
  display: block;
  width: 12px;
  height: 12px;
  background-color: #217ce8;
  transform: scale(1.7);
  opacity: .2;
  border-radius: 60px
}

.arrowblock {
  position: absolute;
  width: 100%;
  overflow: visible;
  pointer-events: none
}

.arrowblock svg {
  width: -webkit-fill-available;
  overflow: visible;
}

.empty-node-border {
  border: 2px solid darkgray;
}

.question-border {
  border: 2px solid cornflowerblue;
}

.exposition-border {
  border: 2px solid rosybrown;
}
</style>
