<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-learning-tree'"/>
    <b-modal
      id="modal-update-node"
      ref="modal"
      title="Update Node"
      size="xl"
      :no-close-on-esc="true"
      hide-footer
    >
      <div v-if="!showUpdateNodeContents">
        <div class="d-flex justify-content-center mb-3">
          <div class="text-center">
            <b-spinner variant="primary" label="Text Centered"></b-spinner> <span style="font-size:30px" class="text-primary"> Loading Contents</span>
          </div>
        </div>
      </div>
      <ViewQuestionWithoutModal :key="`question-to-view-${questionToView.id}`" :question-to-view="questionToView"/>
      <div v-if="showUpdateNodeContents">
        <hr>
        <b-form ref="form">
          <b-form-group
            label-cols-sm="2"
            label-cols-lg="1"
            label="Library*"
            label-for="node_library"
          >
            <div class="mb-2 mr-2" style="width: 300px">
              <b-form-select id="node_library"
                             v-model="nodeForm.library"
                             :options="libraryOptions"
                             :class="{ 'is-invalid': nodeForm.errors.has('library') }"
                             @change="nodeForm.errors.clear('library')"
              />
              <has-error :form="nodeForm" field="library"/>
            </div>
          </b-form-group>
          <b-form-group
            label-cols-sm="2"
            label-cols-lg="1"
            label="Page Id*"
            label-for="page_id"
          >
            <b-form-input
              id="node_page_id"
              v-model="nodeForm.page_id"
              type="text"
              style="width: 100px"
              :class="{ 'is-invalid': nodeForm.errors.has('page_id') }"
              @keydown="nodeForm.errors.clear('page_id')"
            />
            <has-error :form="nodeForm" field="page_id"/>
          </b-form-group>
          <b-form-group>
            <label for="branch_description">Branch Description*</label>
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
        </b-form>
        <div>
          <b-button size="sm" @click="$bvModal.hide('modal-update-node')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary" @click="submitUpdateNode">
            Update
          </b-button>
        </div>
      </div>


    </b-modal>

    <b-modal
      id="modal-learning-tree-details"
      ref="modal"
      title="Learning Tree Details"
      :no-close-on-esc="true"
      @hidden="resetLearningTreeDetailsModal"
    >
      <p v-if="learningTreeId" class="font-italic">
        The assessment question for the root node of this learning tree has a learning tree id of {{ learningTreeId }},
        a page id of {{ assessmentPageId }} and comes from the
        {{ assessmentLibrary }} library.
      </p>
      <RequiredText/>
      <b-form ref="form">
        <b-form-group
          label-cols-sm="5"
          label-cols-lg="4"
          label-for="learning_tree_title"
        >
          <template slot="label">
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
          <template slot="label">
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
        <b-form-group
          v-if="!learningTreeId"
          label-cols-sm="5"
          label-cols-lg="4"
          label-for="library"
        >
          <template slot="label">
            Library*
          </template>
          <div class="mb-2 mr-2">
            <b-form-select v-model="learningTreeForm.library"
                           title="library"
                           :options="libraryOptions"
                           :class="{ 'is-invalid': learningTreeForm.errors.has('library') }"
                           @change="learningTreeForm.errors.clear('library')"
            />
            <has-error :form="learningTreeForm" field="library"/>
          </div>
        </b-form-group>
        <b-form-group
          v-if="!learningTreeId"
          label-cols-sm="5"
          label-cols-lg="4"
          label-for="page_id"
        >
          <template slot="label">
            Page Id*
          </template>
          <b-form-input
            id="page_id"
            v-model="learningTreeForm.page_id"
            type="text"
            style="width: 90px"
            :class="{ 'is-invalid': learningTreeForm.errors.has('page_id') }"
            @keydown="learningTreeForm.errors.clear('page_id')"
          />
          <has-error :form="learningTreeForm" field="page_id"/>
        </b-form-group>
      </b-form>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm"
                  @click="$bvModal.hide('modal-learning-tree-details');resetLearningTreeDetailsModal"
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
    <div style="margin-left:-100px;">
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
    <div v-show="user.role === 2 && !isLearningTreeView" id="leftcard">
      <div id="actions">
        <b-button variant="success" size="sm" @click="initCreateNew">
          New Tree
        </b-button>
        <b-button :class="{ 'disabled': learningTreeId === 0}"
                  :aria-disabled="learningTreeId === 0"
                  variant="primary"
                  size="sm"
                  @click="learningTreeId === 0 ? '' : editLearningTree()"
        >
          Update Info
        </b-button>
        <b-button :class="{ 'disabled': learningTreeId === 0}"
                  :aria-disabled="learningTreeId === 0"
                  variant="danger"
                  size="sm"
                  @click="learningTreeId === 0 ? '' : deleteLearningTree()"
        >
          Delete
        </b-button>
        <div id="search">
          <div class="mb-2 mr-2">
            <b-form-select v-model="library" title="library" :options="libraryOptions" class="mt-3"/>
          </div>
          <div class="d-flex flex-row">
            <b-form-input v-model="pageId" style="width: 90px" placeholder="Page Id"/>
            <b-button :class="{ 'disabled': learningTreeId === 0}"
                      class="ml-2 mr-2"
                      :aria-disabled="learningTreeId === 0"
                      variant="secondary"
                      size="sm"
                      @click="addRemediation"
            >
              <b-spinner v-if="validatingLibraryAndPageId" small label="Spinning"/>
              New Node
            </b-button>
            <b-button size="sm"
                      variant="outline-info"
                      :class="{ 'disabled': !canUndo}"
                      aria-label="Undo"
                      class="mr-2"
                      @click="!canUndo ? '' : undo()"
            >
              <font-awesome-icon :icon="undoIcon"/>
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

import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import libraries from '~/helpers/Libraries'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUndo } from '@fortawesome/free-solid-svg-icons'
import AllFormErrors from '~/components/AllFormErrors'
import { ToggleButton } from 'vue-js-toggle-button'
import ViewQuestionWithoutModal from '~/components/ViewQuestionWithoutModal'
import { h5pResizer } from '~/helpers/H5PResizer'

export default {

  metaInfo () {
    return { title: this.$t('home') }
  },
  components: {
    FontAwesomeIcon,
    ToggleButton,
    AllFormErrors,
    ViewQuestionWithoutModal
  },
  data: () => ({
    showUpdateNodeContents: false,
    questionToView: {},
    allFormErrors: [],
    isLearningTreeView: false,
    nodeSrc: '',
    nodeIframeId: '',
    canUndo: false,
    undoIcon: faUndo,
    nodeForm: new Form({
      library: null,
      page_id: '',
      branch_description: ''
    }),
    nodeToUpdate: {},
    learningTreeForm: new Form({
      title: '',
      description: '',
      library: null,
      page_id: ''
    }),
    assessmentLibrary: '',
    assessmentPageId: '',
    touchingBlock: false,
    validatingLibraryAndPageId: false,
    panelHidden: false,
    studentLearningObjectives: '',
    title: window.config.appName,
    pageId: '',
    chosenId: '',
    learningTreeId: 0,
    library: null,
    libraryColors: {
      'adapt': 'blue',
      'bio': '#00b224',
      'biz': 'rgb(32, 117, 55)',
      'chem': 'rgb(0, 191, 255)',
      'eng': '#ff6a00',
      'espanol': '#d77b00',
      'geo': '#e5a800',
      'human': '#00bc94',
      'k12': '#5cbf1c',
      'law': '#1c5d73',
      'math': '#3737bf',
      'med': '#e52817',
      'query': '#0060bc',
      'phys': '#841fcc',
      'socialsci': '#f20c92',
      'stats': '#05baff',
      'workforce': '#bf4000'
    },
    libraryOptions: libraries
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    h5pResizer()
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to the Learning Tree Editor.')
      return false
    }
    let tempblock
    let tempblock2
    console.log(document.getElementById('canvas'))

    flowy(document.getElementById('canvas'), drag, release, snapping, rearranging, 40, 50)

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

      // let libraryText = vm.getLibraryText(blockin.querySelector('.library').innerHTML)
      // let library = isAssessmentNode ? '' : blockin.querySelector('.library').innerHTML
      //  let pageId = isAssessmentNode ? '' : blockin.querySelector('.pageId').innerHTML
      let title = blockin.querySelector('.title').innerHTML
      let blockynameContents = blockin.querySelector('.blockyname').innerHTML
      let body = isAssessmentNode ? 'The original question'
        : `${blockynameContents}
<span class="extra"></span></div>`
      drag.innerHTML += `<div class="blockyleft">
<p class="blockyname" style="margin-bottom:0;">${body}</p></div>
<div class="blockydiv"></div>
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
      if (event.target.className === 'open-student-learning-objective-modal') {
        vm.pageId = event.target.parentNode.parentNode.querySelector('.pageId').innerHTML
        vm.library = event.target.parentNode.parentNode.querySelector('.library').innerHTML.toLowerCase()
        console.log(vm.pageId)
        console.log(vm.library)
        vm.openStudentLearningObjectiveModal()
      } else if (event.type === 'mouseup' && aclick && !noinfo) {
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

    addEventListener('mousedown', beginTouch, false)
    addEventListener('mousemove', checkTouch, false)
    addEventListener('mouseup', doneTouch, false)
    addEventListenerMulti('touchstart', beginTouch, false, '.block')

    this.learningTreeId = parseInt(this.$route.params.learningTreeId)
    if (this.learningTreeId === 0) {
      this.$bvModal.show('modal-learning-tree-details')
      this.learningTreeForm.library = null
    } else {
      this.getLearningTreeLearningTreeId(this.learningTreeId)
    }
  },
  methods: {
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
      this.nodeForm.errors.clear()
      this.showUpdateNodeContents = false
      this.questionToView = {}
      this.$bvModal.show('modal-update-node')
      this.nodeToUpdate = nodeToUpdate.closest('.block')

      let pageId = this.nodeToUpdate.querySelector('input[name="page_id"]').value

      this.nodeForm.page_id = ''
      let library = this.nodeToUpdate.querySelector('input[name="library"]').value
      this.nodeForm.library = library
      this.nodeForm.page_id = pageId
      await this.getQuestionToView(library, pageId)
      await this.getBranchDescription(library, pageId)
      this.showUpdateNodeContents = true
      this.nodeIframeId = `remediation-${library}-${pageId}`
    },
    async getQuestionToView (library, pageId) {
      try {
        const { data } = await axios.get(`/api/questions/${library}/${pageId}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.questionToView = data.question
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getBranchDescription (library, pageId) {
      try {
        const { data } = await axios.get(`/api/branches/description/${this.learningTreeId}/${library}/${pageId}`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.nodeForm.branch_description = data.description
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitUpdateNode (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.nodeForm.patch(`/api/learning-trees/nodes/${this.learningTreeId}`)
        console.log(data)
        if (data.type === 'success') {
          let left = this.nodeToUpdate.style.left
          let top = this.nodeToUpdate.style.top
          this.nodeToUpdate.setAttribute('style', `border: 1px solid ${this.libraryColors[this.nodeForm.library]}; left: ${left}; top: ${top}`)
          this.nodeToUpdate.querySelector('input[name="page_id"]').value = this.nodeForm.page_id
          this.nodeToUpdate.querySelector('input[name="library"]').value = this.nodeForm.library
          this.nodeToUpdate.querySelector('.blockyinfo').innerHTML = this.shortenString(data.title)
          this.nodeToUpdate.querySelector('.blockyname').innerHTML = this.getBlockyNameHTML(this.nodeForm.library, this.nodeForm.page_id)
          await this.saveLearningTree(this.nodeForm.library, this.nodeForm.page_id)
        } else {
          this.$noty.error(data.message)
        }
        this.$bvModal.hide('modal-update-node')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.nodeForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-learning-tree')
        }
      }
    },
    resetAll () {
      this.learningTreeId = 0
      document.getElementById('canvas').innerHTML = ''
      document.getElementById('blocklist').innerHTML = ''
      this.pageId = ''
      this.library = null
      this.learningTreeForm.library = 'query'
      this.learningTreeForm.page_id = ''
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

      this.$bvModal.show('modal-learning-tree-details')
    },
    resetLearningTreeDetailsModal () {
      this.learningTreeForm.title = ''
      this.learningTreeForm.description = ''
      this.learningTreeForm.errors.clear()
    },
    resetLearningTreeModal (modalId) {
      this.resetLearningTreeDetailsModal()
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
    getLibraryText (library) {
      let text = ''
      for (let i = 0; i < this.libraryOptions.length; i++) {
        if (library === this.libraryOptions[i].value) {
          text = this.libraryOptions[i].text
        }
      }
      return text
    },
    async createLearningTree () {
      try {
        this.learningTreeForm.color = this.libraryColors[this.learningTreeForm.library]
        this.learningTreeForm.text = this.getLibraryText(this.learningTreeForm.library)

        const { data } = await this.learningTreeForm.post('/api/learning-trees/info')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.learningTreeId = data.learning_tree_id
          this.title = this.learningTreeForm.title
          this.description = this.learningTreeForm.description
          this.assessmentLibrary = this.learningTreeForm.text
          this.library = this.setRemediationLibraryByAssessmentLibrary(this.assessmentLibrary)
          this.assessmentPageId = this.learningTreeForm.page_id
          this.$bvModal.hide('modal-learning-tree-details')
          console.log(data.learning_tree)
          let learningTree = data.learning_tree.replaceAll('/assets/img', this.asset('assets/img'))
          flowy.import(JSON.parse(learningTree))
        }
        console.log(this.learningTreeId)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.learningTreeForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-learning-tree')
        }
      }
    },
    setRemediationLibraryByAssessmentLibrary (assessmentLibrary) {
      for (let i = 0; i < this.libraryOptions.length; i++) {
        if (this.libraryOptions[i].text === assessmentLibrary) {
          return this.libraryOptions[i].value
        }
      }
    },
    async updateLearningTreeInfo () {
      try {
        const { data } = await this.learningTreeForm.post(`/api/learning-trees/info/${this.learningTreeId}`)
        this.$noty[data.type](data.message)
        this.title = this.learningTreeForm.title
        this.description = this.learningTreeForm.description
        this.resetLearningTreeModal('modal-learning-tree-details')
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
        this.assessmentPageId = data.page_id
        this.assessmentLibrary = data.library
        this.canUndo = data.can_undo
        this.library = this.setRemediationLibraryByAssessmentLibrary(this.assessmentLibrary)
        if (data.learning_tree) {
          let learningTree = data.learning_tree.replaceAll('/assets/img', this.asset('assets/img'))
          flowy.import(JSON.parse(learningTree))
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveLearningTree (root_node_library = null, root_node_page_id = null) {
      try {
        let learningTree = JSON.stringify(flowy.output()).replaceAll(this.asset('assets/img'), '/assets/img')
        const { data } = await axios.patch(`/api/learning-trees/${this.learningTreeId}`, {
          'learning_tree': learningTree,
          'root_node_library': root_node_library,
          'root_node_page_id': root_node_page_id
        })
        console.log(data)
        if (data.type === 'no_change') {
          return false
        }
        if (data.type === 'success') {
          document.getElementById('blocklist').innerHTML = ''
          this.pageId = ''
        }
        this.$noty[data.type](data.message)
        this.canUndo = data.can_undo
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async openStudentLearningObjectiveModal () {
      const { data } = await axios.get(`/libreverse/library/${this.library}/page/${this.pageId}/student-learning-objectives`)
      let d = document.createElement('div')
      d.innerHTML = data

      const h = this.$createElement

      const titleVNode = h('div', { domProps: { innerHTML: 'Student Learning Objectives' } })
      let messageVNode
      if (d.querySelector('ul')) {
        messageVNode = h('ul', [])
        for (const li of d.querySelector('ul').querySelectorAll('li')) {
          messageVNode.children.push(h('li', [li.textContent]))
        }
      } else {
        messageVNode = h('p', { class: 'text-danger' }, ['There are no Student Learning Objectives available.'])
      }

      console.log(messageVNode)
      // We must pass the generated VNodes as arrays
      this.$bvModal.msgBoxOk([messageVNode], {
        title: [titleVNode],
        buttonSize: 'sm',
        centered: true,
        size: 'lg'
      })

      this.$bvModal.show('student-learning-objective-modal')
    },
    async validateLibraryAndPageId (library, pageId) {
      try {
        const { data } = await axios.get(`/api/learning-trees/validate-remediation/${library}/${pageId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.title
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
    },
    getBlockyNameHTML (library, pageId) {
      let libraryText = this.getLibraryText(library)
      let svg = `assets/img/${library}.svg`
      let src = this.asset(svg)
      return `<img src="${src}" alt="${library}" style="${this.libraryColors[library]}"><span class="library"
      >${libraryText}</span> - <span class="page_id">${pageId}</span>`
    },
    async addRemediation () {
      if (!this.library) {
        this.$noty.error('Please choose a library.')
        return false
      }
      if (!(Number.isInteger(parseFloat(this.pageId)) && parseInt(this.pageId) > 0)) {
        this.$noty.error('Your Page Id should be a positive integer.')
        return false
      }
      let title = await this.validateLibraryAndPageId(this.library, this.pageId)
      if (!title) {
        return false
      }
      let blockElems = document.querySelectorAll('div.blockelem.create-flowy.noselect')
      let blockyNameHTML = this.getBlockyNameHTML(this.library, this.pageId)

      let newBlockElem = `<div class="blockelem create-flowy noselect" style="border: 1px solid ${this.libraryColors[this.library]}">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="${blockElems.length + 2}">
        <input type="hidden" name="page_id" value="${this.pageId}">
        <input type="hidden" name="library" value="${this.library}">
<div class="grabme">
</div>
      <div class="blockin">
        <div class="blockyleft">
          <p class="blockyname"> ${blockyNameHTML} </p>
        </div>
          <div class="blockydiv">
          </div>
          <div class="blockin-info">
          <span class="blockdesc"><b-icon icon="pencil"></b-icon><span class="title">${this.shortenString(title)}</span>
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
    },
    shortenString (html) {
      let doc = new DOMParser().parseFromString(html, 'text/html')
      let string
      string = doc.body.textContent
      if (string === '') {
        return 'No title'
      }
      if (string.length < 29) {
        return string
      }
      return string.substr(0, 29) + '...'
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

.blockdesc, .blockyinfo {
  font-family: Roboto;
  color: #808292;
  font-size: 14px;
  line-height: 21px;
}

.blockyinfo, .blockin-info {
  margin: 20px;
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
  font-family: Roboto;
  font-weight: 500;
  color: #253134;
  display: inline-block;
  vertical-align: middle;
  margin-left: 8px;
  font-size: 16px;
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

.blockyleft {
  display: inline-block;
  margin-left: 20px;
}

.blockydiv {
  width: 100%;
  height: 1px;
  background-color: #E9E9EF;
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

.open-student-learning-objective-modal {

}

.open-student-learning-objective-modal {
  color: #007bff;
  text-decoration: none;
  background-color: transparent;
  -webkit-text-decoration-skip: objects;
}

.open-student-learning-objective-modal:hover {
  color: #0056b3;
  text-decoration: none;

}

</style>
