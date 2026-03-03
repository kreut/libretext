<template>
  <div class="flashcard-viewer">
    <div
      class="flashcard-scene"
      :class="{ 'is-flipped': isFlipped }"
      @click="flip"
    >
      <!-- FRONT FACE -->
      <div class="flashcard-face flashcard-front">
        <component :is="'div'" class="flashcard-face-content">
          <template v-if="card.frontType === 'free_form'">
            <div class="flashcard-rich-content" v-html="card.front"/>
          </template>
          <template v-else-if="card.frontType === 'text_only'">
            <div class="flashcard-term">{{ card.term }}</div>
          </template>
          <template v-else-if="card.frontType === 'text_media'">
            <div class="flashcard-two-col">
              <div class="flashcard-term">{{ card.term }}</div>
              <div class="flashcard-divider"/>
              <div class="flashcard-media">
                <img v-if="card.frontMediaType === 'image' && card.frontMediaUrl" :src="card.frontMediaUrl" :alt="card.frontMediaAlt || ''" class="flashcard-img"/>
                <video v-else-if="card.frontMediaType === 'video' && card.frontMediaUrl" :src="card.frontMediaUrl" controls class="flashcard-video" @click.stop/>
                <span v-else class="flashcard-media-placeholder">No media uploaded</span>
              </div>
            </div>
          </template>
          <template v-else-if="card.frontType === 'media'">
            <div class="flashcard-media-center">
              <img v-if="card.frontMediaType === 'image' && card.frontMediaUrl" :src="card.frontMediaUrl" :alt="card.frontMediaAlt || ''" class="flashcard-img"/>
              <video v-else-if="card.frontMediaType === 'video' && card.frontMediaUrl" :src="card.frontMediaUrl" controls class="flashcard-video" @click.stop/>
              <span v-else class="flashcard-media-placeholder">No media uploaded</span>
            </div>
          </template>
        </component>
        <div class="flashcard-hint">Click to reveal answer</div>
      </div>

      <!-- BACK FACE -->
      <div class="flashcard-face flashcard-back">
        <component :is="'div'" class="flashcard-face-content">
          <template v-if="card.backType === 'free_form'">
            <div class="flashcard-rich-content" v-html="card.back"/>
          </template>
          <template v-else-if="card.backType === 'text_only'">
            <div class="flashcard-answer">{{ card.answer }}</div>
          </template>
          <template v-else-if="card.backType === 'text_media'">
            <div class="flashcard-two-col">
              <div class="flashcard-answer">{{ card.answer }}</div>
              <div class="flashcard-divider"/>
              <div class="flashcard-media">
                <img v-if="card.backMediaType === 'image' && card.backMediaUrl" :src="card.backMediaUrl" :alt="card.backMediaAlt || ''" class="flashcard-img"/>
                <video v-else-if="card.backMediaType === 'video' && card.backMediaUrl" :src="card.backMediaUrl" controls class="flashcard-video" @click.stop/>
                <span v-else class="flashcard-media-placeholder">No media uploaded</span>
              </div>
            </div>
          </template>
          <template v-else-if="card.backType === 'media'">
            <div class="flashcard-media-center">
              <img v-if="card.backMediaType === 'image' && card.backMediaUrl" :src="card.backMediaUrl" :alt="card.backMediaAlt || ''" class="flashcard-img"/>
              <video v-else-if="card.backMediaType === 'video' && card.backMediaUrl" :src="card.backMediaUrl" controls class="flashcard-video" @click.stop/>
              <span v-else class="flashcard-media-placeholder">No media uploaded</span>
            </div>
          </template>
        </component>
        <div class="flashcard-hint">Click to go back</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FlashcardViewer',
  props: {
    card: { type: Object, required: true }
  },
  data () {
    return { isFlipped: false }
  },
  watch: {
    card () { this.isFlipped = false }
  },
  methods: {
    flip () { this.isFlipped = !this.isFlipped }
  }
}
</script>

<style scoped>
.flashcard-viewer { display: flex; justify-content: center; padding: 24px 0; }
.flashcard-scene { width: 100%; max-width: 680px; min-height: 320px; position: relative; cursor: pointer; perspective: 1200px; transform-style: preserve-3d; }
.flashcard-scene.is-flipped .flashcard-front { transform: rotateY(-180deg); }
.flashcard-scene.is-flipped .flashcard-back { transform: rotateY(0deg); }
.flashcard-face { position: absolute; width: 100%; min-height: 320px; top: 0; left: 0; border-radius: 12px; backface-visibility: hidden; -webkit-backface-visibility: hidden; transition: transform 0.55s cubic-bezier(0.45, 0.05, 0.55, 0.95); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 32px; box-sizing: border-box; box-shadow: 0 4px 24px rgba(0,0,0,0.10); user-select: none; }
.flashcard-front { background: #ffffff; border: 2px solid #dee2e6; transform: rotateY(0deg); }
.flashcard-back { background: #f0f4ff; border: 2px solid #b8c8ff; transform: rotateY(180deg); }
.flashcard-face-content { width: 100%; display: flex; justify-content: center; align-items: center; flex: 1; }
.flashcard-rich-content { width: 100%; font-size: 1.05rem; line-height: 1.6; color: #212529; text-align: center; }
.flashcard-two-col { display: flex; flex-direction: row; align-items: center; width: 100%; }
.flashcard-term { flex: 1; font-size: 1.35rem; font-weight: 600; color: #212529; text-align: center; padding-right: 20px; word-break: break-word; }
.flashcard-answer { font-size: 1.35rem; font-weight: 600; color: #212529; text-align: center; width: 100%; }
.flashcard-divider { width: 2px; min-height: 180px; background: #dee2e6; border-radius: 2px; flex-shrink: 0; }
.flashcard-media { flex: 1; display: flex; justify-content: center; align-items: center; padding-left: 20px; }
.flashcard-media-center { display: flex; justify-content: center; align-items: center; width: 100%; }
.flashcard-img { max-width: 100%; max-height: 220px; border-radius: 8px; object-fit: contain; }
.flashcard-video { max-width: 100%; max-height: 220px; border-radius: 8px; }
.flashcard-media-placeholder { color: #adb5bd; font-style: italic; font-size: 0.9rem; }
.flashcard-hint { position: absolute; bottom: 12px; font-size: 0.75rem; color: #adb5bd; letter-spacing: 0.03em; }
</style>
