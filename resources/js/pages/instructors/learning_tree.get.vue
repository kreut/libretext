<template>
  <div>
    <div v-if="user.role === 2" class="font-italic">
      <b-row>
        <b-col class="col-md-3"/>
        <h5>Title: {{ title }}</h5>
      </b-row>
      <b-row>
        <b-col class="col-md-3"/>
        <h5>Description: {{ description }}</h5>
      </b-row>
    </div>
    <div id="canvas"/>
  </div>
</template>

<script>

import { flowy } from '~/helpers/Flowy'

import axios from 'axios'

import { mapGetters } from 'vuex'

export default {

  metaInfo () {
    return { title: this.$t('home') }
  },
  data: () => ({
    learningTreeId: 0,
    title: '',
    description: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    flowy(document.getElementById('canvas'))
    this.learningTreeId = parseInt(this.$route.params.learningTreeId)
    this.getLearningTreeLearningByTreeId(this.learningTreeId)
  },
  methods: {
    async getLearningTreeLearningByTreeId (learningTreeId) {
      try {
        const { data } = await axios.get(`/api/learning-trees/${learningTreeId}`)
        this.title = data.title
        this.description = data.description
        if (data.learning_tree) {
          let learningTree = data.learning_tree.replaceAll('/assets/img', this.asset('assets/img'))
          flowy.import(JSON.parse(learningTree))
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
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
  top: 0;
  left: 341px;
  z-index: 0;
  overflow: auto;
}*/
#canvas {
  position: absolute;
  width: calc(100% - 361px);
  height: 900px;
  top: 0;
  left: 341px;
  z-index: 0;
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
