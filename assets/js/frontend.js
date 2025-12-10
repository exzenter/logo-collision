// Initialize GSAP ScrollTrigger
gsap.registerPlugin(ScrollTrigger);

// Default animation properties (simplified for WordPress version)
const defaultAnimationProps = {
  duration: 0.6,
  ease: 'power4'
};

// Main execution wrapped in IIFE to allow early returns
(function() {
  // Get settings from WordPress
  const settings = typeof caaSettings !== 'undefined' ? caaSettings : {};
  const logoSelector = settings.logoId || '';
  const selectedEffect = parseInt(settings.selectedEffect) || 1;
  const includedElementsStr = settings.includedElements || '';
  const excludedElementsStr = settings.excludedElements || '';
  const globalOffset = parseInt(settings.globalOffset) || 0;
  const debugMode = settings.debugMode === '1';
  
  // Parse offset settings from WordPress (allow negative values)
  const offsetStart = parseInt(settings.offsetStart) || 30;
  const offsetEnd = parseInt(settings.offsetEnd) || 10;

  // Debug logging function
  const debug = {
    log: (...args) => {
      if (debugMode) {
        console.log('[CAA Debug]', ...args);
      }
    },
    warn: (...args) => {
      if (debugMode) {
        console.warn('[CAA Debug]', ...args);
      }
    },
    error: (...args) => {
      if (debugMode) {
        console.error('[CAA Debug]', ...args);
      }
    },
    group: (label) => {
      if (debugMode) {
        console.group('[CAA Debug]', label);
      }
    },
    groupEnd: () => {
      if (debugMode) {
        console.groupEnd();
      }
    }
  };

  debug.log('Plugin initialized', {
    logoSelector,
    selectedEffect,
    includedElements: includedElementsStr,
    excludedElements: excludedElementsStr,
    globalOffset,
    offsetStart,
    offsetEnd,
    debugMode
  });

  // Exit if no logo selector is provided
  if (!logoSelector) {
    console.warn('Context-Aware Animation: No logo ID specified in settings.');
    debug.warn('No logo selector provided - plugin will not run');
    return;
  }

  // Find the logo element
  const logoElement = document.querySelector(logoSelector);
  if (!logoElement) {
    console.warn(`Context-Aware Animation: Logo element not found with selector: ${logoSelector}`);
    debug.warn('Logo element not found with selector:', logoSelector);
    return;
  }

  debug.log('Logo element found:', logoElement);

  // Store original HTML content and styles for the logo element
  const originalHTMLContent = logoElement.innerHTML;
  const originalStyles = logoElement.getAttribute('style') || '';
  
  debug.log('Logo original state saved', {
    hasContent: originalHTMLContent.length > 0,
    hasStyles: originalStyles.length > 0
  });

  // Function to reset the logo element to its original state
  function resetElement(target) {
    // Restore original HTML content
    target.innerHTML = originalHTMLContent;
    // Remove any transformations and set properties back to default values
    gsap.set(target, {
      clearProps: 'all', // Clear all GSAP-applied properties
      rotation: 0,
      xPercent: 0,
      yPercent: 0,
      x: 0,
      y: 0,
      scale: 1,
      autoAlpha: 1,
    });
    // Restore any initial inline styles if present
    target.setAttribute('style', originalStyles);
  }

  // Function to get the logo element's position from the top of the viewport
  function getElementTopOffset(element) {
    const elementRect = element.getBoundingClientRect();
    return elementRect.top; // Returns the distance of the element from the top of the viewport
  }

  // Parse included and excluded elements
  const includedSelectors = includedElementsStr
    .split(',')
    .map(s => s.trim())
    .filter(s => s.length > 0);
  
  const excludedSelectors = excludedElementsStr
    .split(',')
    .map(s => s.trim())
    .filter(s => s.length > 0);

  debug.group('Element Detection');
  debug.log('Included selectors:', includedSelectors);
  debug.log('Excluded selectors:', excludedSelectors);

  // Get all content blocks
  let contentBlocks = [];
  
  // If included elements are specified, use those
  if (includedSelectors.length > 0) {
    debug.log('Using custom included selectors');
    includedSelectors.forEach(selector => {
      try {
        const elements = document.querySelectorAll(selector);
        debug.log(`Selector "${selector}" found ${elements.length} element(s)`);
        elements.forEach(el => {
          // Check if element is excluded
          const isExcluded = excludedSelectors.some(excludedSelector => {
            try {
              return el.matches(excludedSelector) || el.closest(excludedSelector) !== null;
            } catch (e) {
              return false;
            }
          });
          
          if (!isExcluded && el.offsetHeight > 0) {
            contentBlocks.push(el);
            debug.log('Added content block:', el, { selector, offsetHeight: el.offsetHeight });
          } else {
            debug.log('Skipped element:', el, { isExcluded, offsetHeight: el.offsetHeight });
          }
        });
      } catch (e) {
        console.warn(`Context-Aware Animation: Invalid selector "${selector}" in included elements.`);
        debug.error('Invalid selector:', selector, e);
      }
    });
  } else {
    debug.log('Using auto-detection for content blocks');
    // Auto-detect WordPress content blocks (common selectors)
    const contentSelectors = [
      '.entry-content',
      'main',
      '.content',
      '.post-content',
      'article',
      '.wp-block-post-content',
      '.site-content',
      '.content-area'
    ];

    contentSelectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      debug.log(`Auto-detection selector "${selector}" found ${elements.length} element(s)`);
      elements.forEach(el => {
        // Check if element is excluded
        const isExcluded = excludedSelectors.some(excludedSelector => {
          try {
            return el.matches(excludedSelector) || el.closest(excludedSelector) !== null;
          } catch (e) {
            return false;
          }
        });
        
        if (!isExcluded && el.offsetHeight > 0) {
          contentBlocks.push(el);
          debug.log('Added content block (auto-detected):', el, { selector });
        }
      });
    });

    // If no content blocks found, try a more general approach
    if (contentBlocks.length === 0) {
      debug.log('No content blocks found with standard selectors, trying general approach');
      const bodyChildren = Array.from(document.body.children);
      contentBlocks = bodyChildren.filter(el => {
        // Skip header, nav, footer, and excluded elements
        const tagName = el.tagName.toLowerCase();
        if (['header', 'nav', 'footer', 'script', 'style'].includes(tagName)) {
          return false;
        }
        
        // Check if excluded
        const isExcluded = excludedSelectors.some(excludedSelector => {
          try {
            return el.matches(excludedSelector) || el.closest(excludedSelector) !== null;
          } catch (e) {
            return false;
          }
        });
        
        return !isExcluded && el.offsetHeight > 0;
      });
      debug.log(`General approach found ${contentBlocks.length} content block(s)`);
    }
  }

  // Remove duplicates
  contentBlocks = [...new Set(contentBlocks)];
  debug.log(`Total content blocks found: ${contentBlocks.length}`, contentBlocks);
  debug.groupEnd();

  // Helper function to verify SplitType library is available
  // SplitType should be loaded synchronously at page load, so this is just a verification
  function waitForSplitType() {
    // Check for SplitType on window/globalThis
    let SplitTypeLib = globalThis.SplitType || window.SplitType;
    
    // If SplitType exists but might be wrapped in a default export
    if (SplitTypeLib && typeof SplitTypeLib === 'object' && SplitTypeLib.default) {
      SplitTypeLib = SplitTypeLib.default;
    }
    
    if (SplitTypeLib && typeof SplitTypeLib === 'function') {
      return Promise.resolve(SplitTypeLib);
    }
    
    // If not available, throw error immediately (should have loaded at page load)
    throw new Error('SplitType library is not available. It should be loaded synchronously at page load. Ensure the selected effect requires text splitting and SplitType is properly enqueued.');
  }

  // Build effect functions based on selected effect
  function buildEffect() {
    // Merge WordPress settings with defaults
    const animationProps = {
      duration: parseFloat(settings.duration) || defaultAnimationProps.duration,
      ease: settings.ease || defaultAnimationProps.ease
    };
    
    // Use WordPress offset settings for all effects
    const effectOffsetStart = offsetStart;
    const effectOffsetEnd = offsetEnd;
    
    switch (selectedEffect) {
      case 1: // Scale
      const effect1Settings = {
        scaleDown: settings.effect1ScaleDown !== undefined && settings.effect1ScaleDown !== '' ? parseFloat(settings.effect1ScaleDown) : 0,
        originX1: settings.effect1OriginX !== undefined && settings.effect1OriginX !== '' ? parseInt(settings.effect1OriginX) : 0,
        originY1: settings.effect1OriginY !== undefined && settings.effect1OriginY !== '' ? parseInt(settings.effect1OriginY) : 50
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          gsap.set(target, { transformOrigin: `${effect1Settings.originX1}% ${effect1Settings.originY1}%` });
          target.currentTween = gsap.to(target, {
            scale: effect1Settings.scaleDown,
            autoAlpha: 0,
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            scale: 1,
            autoAlpha: 1,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 2: // Blur
      const effect2Settings = {
        blurAmount: settings.effect2BlurAmount !== undefined && settings.effect2BlurAmount !== '' ? parseFloat(settings.effect2BlurAmount) : 5,
        blurScale: settings.effect2BlurScale !== undefined && settings.effect2BlurScale !== '' ? parseFloat(settings.effect2BlurScale) : 0.9,
        blurDuration: settings.effect2BlurDuration !== undefined && settings.effect2BlurDuration !== '' ? parseFloat(settings.effect2BlurDuration) : 0.2
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          gsap.set(target, { transformOrigin: '0% 50%' });
          target.currentTween = gsap.to(target, {
            startAt: { filter: 'blur(0px)' },
            filter: `blur(${effect2Settings.blurAmount}px)`,
            scale: effect2Settings.blurScale,
            duration: effect2Settings.blurDuration,
            ease: 'sine',
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            filter: 'blur(0px)',
            scale: 1,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 3: // Slide Text
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          const innerContent = target.innerHTML;
          target.innerHTML = `<div class="oh__inner">${innerContent}</div>`;
          target.classList.add('oh');
          gsap.set(target, { transformOrigin: '50% 50%' });
          target.currentTween = gsap.to(target.querySelector('.oh__inner'), {
            yPercent: -102,
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target.querySelector('.oh__inner'), {
            startAt: { yPercent: 102 },
            yPercent: 0,
            ...animationProps,
            onComplete: () => {
              target.innerHTML = originalHTMLContent;
              target.classList.remove('oh');
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 4: // Text Split
      const effect4Settings = {
        textXRange: settings.effect4TextXRange !== undefined && settings.effect4TextXRange !== '' ? parseInt(settings.effect4TextXRange) : 50,
        textYRange: settings.effect4TextYRange !== undefined && settings.effect4TextYRange !== '' ? parseInt(settings.effect4TextYRange) : 40,
        staggerAmount: settings.effect4StaggerAmount !== undefined && settings.effect4StaggerAmount !== '' ? parseFloat(settings.effect4StaggerAmount) : 0.03
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: async (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          // Wait for SplitType to be available before importing TextSplitter
          await waitForSplitType();
          // Dynamically import TextSplitter only when needed
          const { TextSplitter } = await import('./textSplitter.js');
          target.textSplitter = new TextSplitter(target, { splitTypeTypes: 'chars' });
          target.currentTween = gsap.to(target.textSplitter.getChars(), {
            x: () => gsap.utils.random(-effect4Settings.textXRange, effect4Settings.textXRange),
            y: () => gsap.utils.random(-effect4Settings.textYRange, 0),
            autoAlpha: 0,
            stagger: {
              amount: effect4Settings.staggerAmount,
              from: 'random'
            },
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          const chars = target.textSplitter?.getChars?.();
          if (!chars) {
            resetElement(target);
            target.currentTween = null;
            return;
          }
          target.currentTween = gsap.to(chars, {
            x: 0,
            y: 0,
            autoAlpha: 1,
            stagger: {
              amount: effect4Settings.staggerAmount,
              from: 'random'
            },
            ...animationProps,
            onComplete: () => {
              target.innerHTML = originalHTMLContent;
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 5: // Character Shuffle
      const effect5Settings = {
        shuffleIterations: settings.effect5ShuffleIterations !== undefined && settings.effect5ShuffleIterations !== '' ? parseInt(settings.effect5ShuffleIterations) : 2,
        shuffleDuration: settings.effect5ShuffleDuration !== undefined && settings.effect5ShuffleDuration !== '' ? parseFloat(settings.effect5ShuffleDuration) : 0.03,
        charDelay: settings.effect5CharDelay !== undefined && settings.effect5CharDelay !== '' ? parseFloat(settings.effect5CharDelay) : 0.03
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: async (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          // Wait for SplitType to be available before importing TextSplitter
          await waitForSplitType();
          // Dynamically import TextSplitter only when needed
          const { TextSplitter } = await import('./textSplitter.js');
          target.textSplitter = new TextSplitter(target, { splitTypeTypes: 'chars' });
          target.currentTween = gsap.to(target.textSplitter.getChars(), {
            duration: 0.02,
            ease: 'none',
            autoAlpha: 0,
            stagger: {
              amount: 0.25,
              from: 'end'
            },
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          const chars = target.textSplitter?.getChars?.();
          if (!chars) {
            resetElement(target);
            target.currentTween = null;
            return;
          }
          const getRandomChar = () => {
            const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            return letters.charAt(Math.floor(Math.random() * letters.length));
          };
      
          const tl = gsap.timeline({
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            }
          });
      
          chars.forEach((char, index) => {
            const originalChar = char.innerHTML;
      
            for (let i = 0; i < effect5Settings.shuffleIterations; i++) {
              tl.to(char, {
                duration: effect5Settings.shuffleDuration,
                textContent: getRandomChar(),
                autoAlpha: 1,
                ease: 'none'
              });
            }
      
            tl.to(char, {
              duration: 0.02,
              textContent: originalChar,
              autoAlpha: 1,
              ease: 'none'
            });
      
            tl.add('', index * effect5Settings.charDelay);
          });
      
          target.currentTween = tl;
        }
      };
      
      case 6: // Rotation
      const effect6Settings = {
        rotation: settings.effect6Rotation !== undefined && settings.effect6Rotation !== '' ? parseInt(settings.effect6Rotation) : -90,
        xPercent: settings.effect6XPercent !== undefined && settings.effect6XPercent !== '' ? parseInt(settings.effect6XPercent) : -5,
        originX6: settings.effect6OriginX !== undefined && settings.effect6OriginX !== '' ? parseInt(settings.effect6OriginX) : 0,
        originY6: settings.effect6OriginY !== undefined && settings.effect6OriginY !== '' ? parseInt(settings.effect6OriginY) : 100
      };
      debug.log('Effect 6 settings loaded:', {
        rotation: effect6Settings.rotation,
        xPercent: effect6Settings.xPercent,
        originX6: effect6Settings.originX6,
        originY6: effect6Settings.originY6,
        fromWordPress: {
          rotation: settings.effect6Rotation,
          xPercent: settings.effect6XPercent,
          originX: settings.effect6OriginX,
          originY: settings.effect6OriginY
        }
      });
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          gsap.set(target, { transformOrigin: `${effect6Settings.originX6}% ${effect6Settings.originY6}%` });
          target.currentTween = gsap.to(target, {
            xPercent: effect6Settings.xPercent,
            rotation: effect6Settings.rotation,
            y: () => target.offsetWidth - target.offsetHeight,
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            rotation: 0,
            xPercent: 0,
            y: 0,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            },
          });
        }
      };
      
      case 7: // Move Away
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          target.currentTween = gsap.to(target, {
            x: () => -1 * (target.offsetWidth + target.offsetLeft),
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            x: 0,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            },
          });
        }
      };
      
      default:
        return null;
    }
  }

  // Get the effect configuration
  debug.group('Effect Configuration');
  debug.log('Building effect for selected effect:', selectedEffect);
  const effect = buildEffect();

  if (!effect) {
    console.warn(`Context-Aware Animation: Invalid effect selected: ${selectedEffect}`);
    debug.error('Invalid effect selected:', selectedEffect);
    debug.groupEnd();
    return;
  }

  debug.log('Effect built successfully:', {
    offsetStartAmount: effect.offsetStartAmount,
    offsetEndAmount: effect.offsetEndAmount,
    hasOnEnter: typeof effect.onEnter === 'function',
    hasOnLeave: typeof effect.onLeave === 'function'
  });
  debug.groupEnd();

  // Store ScrollTrigger instances for cleanup
  let scrollTriggerInstances = [];
  
  // Reference counter to track active triggers (prevents flickering with adjacent triggers)
  let activeTriggerCount = 0;

  // Function to create ScrollTriggers for each content block
  function createScrollTriggers() {
    debug.group('ScrollTrigger Creation');
    debug.log('Creating ScrollTriggers for', contentBlocks.length, 'content block(s)');
    
    // Kill existing triggers
    if (scrollTriggerInstances.length > 0) {
      debug.log('Killing', scrollTriggerInstances.length, 'existing trigger(s)');
      scrollTriggerInstances.forEach(instance => instance.kill());
    }
    scrollTriggerInstances = [];
    
    // Reset active trigger count when recreating triggers
    activeTriggerCount = 0;
    
    if (contentBlocks.length === 0) {
      console.warn('Context-Aware Animation: No content blocks found.');
      debug.warn('No content blocks found - cannot create ScrollTriggers');
      debug.groupEnd();
      return;
    }
    
    // Calculate logo offset once globally for all triggers
    const elementOffsetTop = getElementTopOffset(logoElement);
    
    contentBlocks.forEach((block, index) => {
      const startOffset = elementOffsetTop + effect.offsetStartAmount + globalOffset;
      const endOffset = elementOffsetTop - effect.offsetEndAmount + globalOffset;

      debug.log(`Creating ScrollTrigger ${index + 1}/${contentBlocks.length}`, {
        block,
        logoOffsetTop: elementOffsetTop,
        globalOffset,
        start: `top ${startOffset}px`,
        end: `bottom ${endOffset}px`
      });

      const trigger = ScrollTrigger.create({
        trigger: block,
        start: () => `top ${elementOffsetTop + effect.offsetStartAmount + globalOffset}px`,
        end: () => `bottom ${elementOffsetTop - effect.offsetEndAmount + globalOffset}px`,

        onEnter: () => {
          debug.log('ScrollTrigger: onEnter', block, 'Active count:', activeTriggerCount);
          activeTriggerCount++;
          // Only call onEnter when this is the first active trigger (count goes from 0 to 1)
          if (activeTriggerCount === 1) {
            debug.log('First trigger activated, calling effect.onEnter');
            effect.onEnter(logoElement);
          } else {
            debug.log('Additional trigger activated, skipping effect.onEnter (count:', activeTriggerCount + ')');
          }
        },
        onLeaveBack: () => {
          debug.log('ScrollTrigger: onLeaveBack', block, 'Active count:', activeTriggerCount);
          activeTriggerCount--;
          // Only call onLeave when this is the last active trigger (count goes from 1 to 0)
          if (activeTriggerCount === 0) {
            debug.log('Last trigger deactivated, calling effect.onLeave');
            effect.onLeave(logoElement);
          } else {
            debug.log('Trigger deactivated but others still active, skipping effect.onLeave (count:', activeTriggerCount + ')');
          }
          // Ensure count doesn't go negative (safety check)
          if (activeTriggerCount < 0) {
            debug.warn('Active trigger count went negative, resetting to 0');
            activeTriggerCount = 0;
          }
        },
        onLeave: () => {
          debug.log('ScrollTrigger: onLeave', block, 'Active count:', activeTriggerCount);
          activeTriggerCount--;
          // Only call onLeave when this is the last active trigger (count goes from 1 to 0)
          if (activeTriggerCount === 0) {
            debug.log('Last trigger deactivated, calling effect.onLeave');
            effect.onLeave(logoElement);
          } else {
            debug.log('Trigger deactivated but others still active, skipping effect.onLeave (count:', activeTriggerCount + ')');
          }
          // Ensure count doesn't go negative (safety check)
          if (activeTriggerCount < 0) {
            debug.warn('Active trigger count went negative, resetting to 0');
            activeTriggerCount = 0;
          }
        },
        onEnterBack: () => {
          debug.log('ScrollTrigger: onEnterBack', block, 'Active count:', activeTriggerCount);
          activeTriggerCount++;
          // Only call onEnter when this is the first active trigger (count goes from 0 to 1)
          if (activeTriggerCount === 1) {
            debug.log('First trigger activated, calling effect.onEnter');
            effect.onEnter(logoElement);
          } else {
            debug.log('Additional trigger activated, skipping effect.onEnter (count:', activeTriggerCount + ')');
          }
        }
      });
      
      scrollTriggerInstances.push(trigger);
      debug.log(`ScrollTrigger ${index + 1} created successfully`);
    });
    
    debug.log('All ScrollTriggers created:', scrollTriggerInstances.length);
    debug.groupEnd();
  }

  // Wait until the page is fully loaded to create the ScrollTriggers
  window.addEventListener('load', () => {
    debug.log('Page loaded, creating ScrollTriggers');
    createScrollTriggers();
  });

  // Update position dynamically on resize
  window.addEventListener('resize', () => {
    debug.log('Window resized, refreshing ScrollTrigger');
    ScrollTrigger.refresh();
  });
  
  debug.log('Event listeners registered');

})(); // End of IIFE

