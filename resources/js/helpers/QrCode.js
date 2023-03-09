import { asset } from '@codinglabs/laravel-asset'

export let qrCodeConfig = {
  'width': 300,
  'height': 300,
  'data': 'https://qr-code-styling.com',
  'margin': 0,
  'qrOptions': {
    'typeNumber': '0',
    'mode': 'Byte',
    'errorCorrectionLevel': 'H'
  },
  'imageOptions': {
    'hideBackgroundDots': true,
    'imageSize': 0.6,
    'margin': 2
  },
  'dotsOptions': {
    'type': 'dots',
    'color': '#000000',
    'gradient': null
  },
  'backgroundOptions': {
    'color': '#ffffff',
    'gradient': null
  },
  image: asset('assets/img/QR.svg'),
  'dotsOptionsHelper': {
    'colorType': {
      'single': true,
      'gradient': false
    },
    'gradient': {
      'linear': true,
      'radial': false,
      'color1': '#6a1a4c',
      'color2': '#6a1a4c',
      'rotation': '0'
    }
  },
  'cornersSquareOptions': {
    'type': 'square',
    'color': '#000000'
  },
  'cornersSquareOptionsHelper': {
    'colorType': {
      'single': true,
      'gradient': false
    },
    'gradient': {
      'linear': true,
      'radial': false,
      'color1': '#000000',
      'color2': '#000000',
      'rotation': '0'
    }
  },
  'cornersDotOptions': {
    'type': 'square',
    'color': '#000000'
  },
  'cornersDotOptionsHelper': {
    'colorType': {
      'single': true,
      'gradient': false
    },
    'gradient': {
      'linear': true,
      'radial': false,
      'color1': '#000000',
      'color2': '#000000',
      'rotation': '0'
    }
  },
  'backgroundOptionsHelper': {
    'colorType': {
      'single': true,
      'gradient': false
    },
    'gradient': {
      'linear': true,
      'radial': false,
      'color1': '#ffffff',
      'color2': '#ffffff',
      'rotation': '0'
    }
  }
}
