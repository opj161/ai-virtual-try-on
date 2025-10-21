# Image generation with Gemini (aka Nano Banana)  |  Gemini API  |  Google AI for Developers
[Skip to main content](#main-content)

*   [Gemini API](https://ai.google.dev/gemini-api/docs)
    *   [Gemini API docs](https://ai.google.dev/gemini-api/docs)
    *   [API Reference](https://ai.google.dev/api)
*   [Get API key](https://aistudio.google.com/apikey)
*   [Cookbook](https://github.com/google-gemini/cookbook)
*   [Community](https://discuss.ai.google.dev/c/gemini-api/)

*   Get started
    
*   [Overview](https://ai.google.dev/gemini-api/docs)
*   [Quickstart](https://ai.google.dev/gemini-api/docs/quickstart)
*   [API keys](https://ai.google.dev/gemini-api/docs/api-key)
*   [Libraries](https://ai.google.dev/gemini-api/docs/libraries)
*   [OpenAI compatibility](https://ai.google.dev/gemini-api/docs/openai)
*   Models
    
*   [Gemini](https://ai.google.dev/gemini-api/docs/models)
*   [Imagen (image generation)](https://ai.google.dev/gemini-api/docs/imagen)
*   [Veo (video generation)](https://ai.google.dev/gemini-api/docs/video)
*   [Lyria (music generation)](https://ai.google.dev/gemini-api/docs/music-generation)
*   [Embeddings](https://ai.google.dev/gemini-api/docs/embeddings)
*   [Robotics](https://ai.google.dev/gemini-api/docs/robotics-overview)
*   [Pricing](https://ai.google.dev/gemini-api/docs/pricing)
*   [Rate limits](https://ai.google.dev/gemini-api/docs/rate-limits)
*   [Billing info](https://ai.google.dev/gemini-api/docs/billing)
*   Core Capabilities
    
*   [Text generation](https://ai.google.dev/gemini-api/docs/text-generation)
*   [Image generation](https://ai.google.dev/gemini-api/docs/image-generation)
*   [Speech generation](https://ai.google.dev/gemini-api/docs/speech-generation)
*   [Long context](https://ai.google.dev/gemini-api/docs/long-context)
*   [Structured output](https://ai.google.dev/gemini-api/docs/structured-output)
*   [Thinking](https://ai.google.dev/gemini-api/docs/thinking)
*   [Document understanding](https://ai.google.dev/gemini-api/docs/document-processing)
*   [Image understanding](https://ai.google.dev/gemini-api/docs/image-understanding)
*   [Video understanding](https://ai.google.dev/gemini-api/docs/video-understanding)
*   [Audio understanding](https://ai.google.dev/gemini-api/docs/audio)
*   [Function calling](https://ai.google.dev/gemini-api/docs/function-calling)
*   Tools
    
*   [Google Search](https://ai.google.dev/gemini-api/docs/google-search)
*   [Google Maps](https://ai.google.dev/gemini-api/docs/maps-grounding)
*   [Code execution](https://ai.google.dev/gemini-api/docs/code-execution)
*   [URL context](https://ai.google.dev/gemini-api/docs/url-context)
*   [Computer Use](https://ai.google.dev/gemini-api/docs/computer-use)
*   Live API
    
*   [Get Started](https://ai.google.dev/gemini-api/docs/live)
*   [Capabilities](https://ai.google.dev/gemini-api/docs/live-guide)
*   [Tool use](https://ai.google.dev/gemini-api/docs/live-tools)
*   [Session management](https://ai.google.dev/gemini-api/docs/live-session)
*   [Ephemeral tokens](https://ai.google.dev/gemini-api/docs/ephemeral-tokens)
*   Guides
    
*   [Batch API](https://ai.google.dev/gemini-api/docs/batch-api)
*   [Context caching](https://ai.google.dev/gemini-api/docs/caching)
*   [Files API](https://ai.google.dev/gemini-api/docs/files)
*   [Token counting](https://ai.google.dev/gemini-api/docs/tokens)
*   [Prompt engineering](https://ai.google.dev/gemini-api/docs/prompting-strategies)

*   Resources
    
*   [Migrate to Gen AI SDK](https://ai.google.dev/gemini-api/docs/migrate)
*   [Release notes](https://ai.google.dev/gemini-api/docs/changelog)
*   [API troubleshooting](https://ai.google.dev/gemini-api/docs/troubleshooting)
*   [Fine-tuning](https://ai.google.dev/gemini-api/docs/model-tuning)


Gemini can generate and process images conversationally. You can prompt Gemini with text, images, or a combination of both allowing you to create, edit, and iterate on visuals with unprecedented control:

*   **Text-to-Image:** Generate high-quality images from simple or complex text descriptions.
*   **Image + Text-to-Image (Editing):** Provide an image and use text prompts to add, remove, or modify elements, change the style, or adjust the color grading.
*   **Multi-Image to Image (Composition & Style Transfer):** Use multiple input images to compose a new scene or transfer the style from one image to another.
*   **Iterative Refinement:** Engage in a conversation to progressively refine your image over multiple turns, making small adjustments until it's perfect.
*   **High-Fidelity Text Rendering:** Accurately generate images that contain legible and well-placed text, ideal for logos, diagrams, and posters.


Image generation (text-to-image)
--------------------------------

The following code demonstrates how to generate an image based on a descriptive prompt.



### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "Create a picture of a nano banana dish in a fancy restaurant with a Gemini theme"}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > gemini-native-image.png

```


![AI-generated image of a nano banana dish](https://ai.google.dev/static/gemini-api/docs/images/nano-banana.png)

AI-generated image of a nano banana dish in a Gemini-themed restaurant

Image editing (text-and-image-to-image)
---------------------------------------

**Reminder**: Make sure you have the necessary rights to any images you upload. Don't generate content that infringe on others' rights, including videos or images that deceive, harass, or harm. Your use of this generative AI service is subject to our [Prohibited Use Policy](https://policies.google.com/terms/generative-ai/use-policy).

The following example demonstrates uploading base64 encoded images. For multiple images, larger payloads, and supported MIME types, check the [Image understanding](https://ai.google.dev/gemini-api/docs/image-understanding) page.


### REST

```
IMG_PATH=/path/to/cat_image.jpeg

if [[ "$(base64 --version 2>&1)" = *"FreeBSD"* ]]; then
  B64FLAGS="--input"
else
  B64FLAGS="-w0"
fi

IMG_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH" 2>&1)

curl -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
    -H "x-goog-api-key: $GEMINI_API_KEY" \
    -H 'Content-Type: application/json' \
    -d "{
      \"contents\": [{
        \"parts\":[
            {\"text\": \"'Create a picture of my cat eating a nano-banana in a fancy restaurant under the Gemini constellation\"},
            {
              \"inline_data\": {
                \"mime_type\":\"image/jpeg\",
                \"data\": \"$IMG_BASE64\"
              }
            }
        ]
      }]
    }"  \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > gemini-edited-image.png

```


![AI-generated image of a cat eating anano banana](https://ai.google.dev/static/gemini-api/docs/images/cat-banana.png)

AI-generated image of a cat eating a nano banana

Other image generation modes
----------------------------

Gemini supports other image interaction modes based on prompt structure and context, including:

*   **Text to image(s) and text (interleaved):** Outputs images with related text.
    *   Example prompt: "Generate an illustrated recipe for a paella."
*   **Image(s) and text to image(s) and text (interleaved)**: Uses input images and text to create new related images and text.
    *   Example prompt: (With an image of a furnished room) "What other color sofas would work in my space? can you update the image?"
*   **Multi-turn image editing (chat):** Keep generating and editing images conversationally.
    *   Example prompts: \[upload an image of a blue car.\] , "Turn this car into a convertible.", "Now change the color to yellow."

Prompting guide and strategies
------------------------------

Mastering Gemini 2.5 Flash Image Generation starts with one fundamental principle:

> **Describe the scene, don't just list keywords.** The model's core strength is its deep language understanding. A narrative, descriptive paragraph will almost always produce a better, more coherent image than a list of disconnected words.

### Prompts for generating images

The following strategies will help you create effective prompts to generate exactly the images you're looking for.

#### 1\. Photorealistic scenes

For realistic images, use photography terms. Mention camera angles, lens types, lighting, and fine details to guide the model toward a photorealistic result.

### Template

```
A photorealistic [shot type] of [subject], [action or expression], set in
[environment]. The scene is illuminated by [lighting description], creating
a [mood] atmosphere. Captured with a [camera/lens details], emphasizing
[key textures and details]. The image should be in a [aspect ratio] format.

```


### Prompt

```
A photorealistic close-up portrait of an elderly Japanese ceramicist with
deep, sun-etched wrinkles and a warm, knowing smile. He is carefully
inspecting a freshly glazed tea bowl. The setting is his rustic,
sun-drenched workshop. The scene is illuminated by soft, golden hour light
streaming through a window, highlighting the fine texture of the clay.
Captured with an 85mm portrait lens, resulting in a soft, blurred background
(bokeh). The overall mood is serene and masterful. Vertical portrait
orientation.

```



### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "A photorealistic close-up portrait of an elderly Japanese ceramicist with deep, sun-etched wrinkles and a warm, knowing smile. He is carefully inspecting a freshly glazed tea bowl. The setting is his rustic, sun-drenched workshop with pottery wheels and shelves of clay pots in the background. The scene is illuminated by soft, golden hour light streaming through a window, highlighting the fine texture of the clay and the fabric of his apron. Captured with an 85mm portrait lens, resulting in a soft, blurred background (bokeh). The overall mood is serene and masterful."}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > photorealistic_example.png

```


![A photorealistic close-up portrait of an elderly Japanese ceramicist...](https://ai.google.dev/static/gemini-api/docs/images/photorealistic_example.png)

A photorealistic close-up portrait of an elderly Japanese ceramicist...

#### 2\. Stylized illustrations & stickers

To create stickers, icons, or assets, be explicit about the style and request a transparent background.

### Template

```
A [style] sticker of a [subject], featuring [key characteristics] and a
[color palette]. The design should have [line style] and [shading style].
The background must be transparent.

```


### Prompt

```
A kawaii-style sticker of a happy red panda wearing a tiny bamboo hat. It's
munching on a green bamboo leaf. The design features bold, clean outlines,
simple cel-shading, and a vibrant color palette. The background must be white.

```



### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "A kawaii-style sticker of a happy red panda wearing a tiny bamboo hat. It'"'"'s munching on a green bamboo leaf. The design features bold, clean outlines, simple cel-shading, and a vibrant color palette. The background must be white."}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > red_panda_sticker.png

```


![A kawaii-style sticker of a happy red...](https://ai.google.dev/static/gemini-api/docs/images/red_panda_sticker.png)

A kawaii-style sticker of a happy red panda...

#### 3\. Accurate text in images

Gemini excels at rendering text. Be clear about the text, the font style (descriptively), and the overall design.

### Template

```
Create a [image type] for [brand/concept] with the text "[text to render]"
in a [font style]. The design should be [style description], with a
[color scheme].

```


### Prompt

```
Create a modern, minimalist logo for a coffee shop called 'The Daily Grind'.
The text should be in a clean, bold, sans-serif font. The design should
feature a simple, stylized icon of a a coffee bean seamlessly integrated
with the text. The color scheme is black and white.

```




### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "Create a modern, minimalist logo for a coffee shop called '"'"'The Daily Grind'"'"'. The text should be in a clean, bold, sans-serif font. The design should feature a simple, stylized icon of a a coffee bean seamlessly integrated with the text. The color scheme is black and white."}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > logo_example.png

```


![Create a modern, minimalist logo for a coffee shop called 'The Daily Grind'...](https://ai.google.dev/static/gemini-api/docs/images/logo_example.png)

Create a modern, minimalist logo for a coffee shop called 'The Daily Grind'...

#### 4\. Product mockups & commercial photography

Perfect for creating clean, professional product shots for e-commerce, advertising, or branding.

### Template

```
A high-resolution, studio-lit product photograph of a [product description]
on a [background surface/description]. The lighting is a [lighting setup,
e.g., three-point softbox setup] to [lighting purpose]. The camera angle is
a [angle type] to showcase [specific feature]. Ultra-realistic, with sharp
focus on [key detail]. [Aspect ratio].

```


### Prompt

```
A high-resolution, studio-lit product photograph of a minimalist ceramic
coffee mug in matte black, presented on a polished concrete surface. The
lighting is a three-point softbox setup designed to create soft, diffused
highlights and eliminate harsh shadows. The camera angle is a slightly
elevated 45-degree shot to showcase its clean lines. Ultra-realistic, with
sharp focus on the steam rising from the coffee. Square image.

```



### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "A high-resolution, studio-lit product photograph of a minimalist ceramic coffee mug in matte black, presented on a polished concrete surface. The lighting is a three-point softbox setup designed to create soft, diffused highlights and eliminate harsh shadows. The camera angle is a slightly elevated 45-degree shot to showcase its clean lines. Ultra-realistic, with sharp focus on the steam rising from the coffee. Square image."}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > product_mockup.png

```


![A high-resolution, studio-lit product photograph of a minimalist ceramic coffee mug...](https://ai.google.dev/static/gemini-api/docs/images/product_mockup.png)

A high-resolution, studio-lit product photograph of a minimalist ceramic coffee mug...

#### 5\. Minimalist & negative space design

Excellent for creating backgrounds for websites, presentations, or marketing materials where text will be overlaid.

### Template

```
A minimalist composition featuring a single [subject] positioned in the
[bottom-right/top-left/etc.] of the frame. The background is a vast, empty
[color] canvas, creating significant negative space. Soft, subtle lighting.
[Aspect ratio].

```


### Prompt

```
A minimalist composition featuring a single, delicate red maple leaf
positioned in the bottom-right of the frame. The background is a vast, empty
off-white canvas, creating significant negative space for text. Soft,
diffused lighting from the top left. Square image.

```




### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "A minimalist composition featuring a single, delicate red maple leaf positioned in the bottom-right of the frame. The background is a vast, empty off-white canvas, creating significant negative space for text. Soft, diffused lighting from the top left. Square image."}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > minimalist_design.png

```


![A minimalist composition featuring a single, delicate red maple leaf...](https://ai.google.dev/static/gemini-api/docs/images/minimalist_design.png)

A minimalist composition featuring a single, delicate red maple leaf...

#### 6\. Sequential art (Comic panel / Storyboard)

Builds on character consistency and scene description to create panels for visual storytelling.

### Template

```
A single comic book panel in a [art style] style. In the foreground,
[character description and action]. In the background, [setting details].
The panel has a [dialogue/caption box] with the text "[Text]". The lighting
creates a [mood] mood. [Aspect ratio].

```


### Prompt

```
A single comic book panel in a gritty, noir art style with high-contrast
black and white inks. In the foreground, a detective in a trench coat stands
under a flickering streetlamp, rain soaking his shoulders. In the
background, the neon sign of a desolate bar reflects in a puddle. A caption
box at the top reads "The city was a tough place to keep secrets." The
lighting is harsh, creating a dramatic, somber mood. Landscape.

```



### REST

```
curl -s -X POST
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "contents": [{
      "parts": [
        {"text": "A single comic book panel in a gritty, noir art style with high-contrast black and white inks. In the foreground, a detective in a trench coat stands under a flickering streetlamp, rain soaking his shoulders. In the background, the neon sign of a desolate bar reflects in a puddle. A caption box at the top reads \"The city was a tough place to keep secrets.\" The lighting is harsh, creating a dramatic, somber mood. Landscape."}
      ]
    }]
  }' \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > comic_panel.png

```


![A single comic book panel in a gritty, noir art style...](https://ai.google.dev/static/gemini-api/docs/images/comic_panel.png)

A single comic book panel in a gritty, noir art style...

### Prompts for editing images

These examples show how to provide images alongside your text prompts for editing, composition, and style transfer.

#### 1\. Adding and removing elements

Provide an image and describe your change. The model will match the original image's style, lighting, and perspective.

### Template

```
Using the provided image of [subject], please [add/remove/modify] [element]
to/from the scene. Ensure the change is [description of how the change should
integrate].

```


### Prompt

```
"Using the provided image of my cat, please add a small, knitted wizard hat
on its head. Make it look like it's sitting comfortably and matches the soft
lighting of the photo."

```



### REST

```
IMG_PATH=/path/to/your/cat_photo.png

if [[ "$(base64 --version 2>&1)" = *"FreeBSD"* ]]; then
  B64FLAGS="--input"
else
  B64FLAGS="-w0"
fi

IMG_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH" 2>&1)

curl -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
    -H "x-goog-api-key: $GEMINI_API_KEY" \
    -H 'Content-Type: application/json' \
    -d "{
      \"contents\": [{
        \"parts\":[
            {\"text\": \"Using the provided image of my cat, please add a small, knitted wizard hat on its head. Make it look like it's sitting comfortably and not falling off.\"},
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG_BASE64\"
              }
            }
        ]
      }]
    }"  \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > cat_with_hat.png

```




* Input:                                   A photorealistic picture of a fluffy ginger cat...                  
  * Output:                                   Using the provided image of my cat, please add a small, knitted wizard hat...                  


#### 2\. Inpainting (Semantic masking)

Conversationally define a "mask" to edit a specific part of an image while leaving the rest untouched.

### Template

```
Using the provided image, change only the [specific element] to [new
element/description]. Keep everything else in the image exactly the same,
preserving the original style, lighting, and composition.

```


### Prompt

```
"Using the provided image of a living room, change only the blue sofa to be
a vintage, brown leather chesterfield sofa. Keep the rest of the room,
including the pillows on the sofa and the lighting, unchanged."

```



### REST

```
IMG_PATH=/path/to/your/living_room.png

if [[ "$(base64 --version 2>&1)" = *"FreeBSD"* ]]; then
  B64FLAGS="--input"
else
  B64FLAGS="-w0"
fi

IMG_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH" 2>&1)

curl -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
    -H "x-goog-api-key: $GEMINI_API_KEY" \
    -H 'Content-Type: application/json' \
    -d "{
      \"contents\": [{
        \"parts\":[
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG_BASE64\"
              }
            },
            {\"text\": \"Using the provided image of a living room, change only the blue sofa to be a vintage, brown leather chesterfield sofa. Keep the rest of the room, including the pillows on the sofa and the lighting, unchanged.\"}
        ]
      }]
    }"  \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > living_room_edited.png

```




* Input:                                         A wide shot of a modern, well-lit living room...                    
  * Output:                                         Using the provided image of a living room, change only the blue sofa to be a vintage, brown leather chesterfield sofa...                    


#### 3\. Style transfer

Provide an image and ask the model to recreate its content in a different artistic style.

### Template

```
Transform the provided photograph of [subject] into the artistic style of [artist/art style]. Preserve the original composition but render it with [description of stylistic elements].

```


### Prompt

```
"Transform the provided photograph of a modern city street at night into the artistic style of Vincent van Gogh's 'Starry Night'. Preserve the original composition of buildings and cars, but render all elements with swirling, impasto brushstrokes and a dramatic palette of deep blues and bright yellows."

```



### REST

```
IMG_PATH=/path/to/your/city.png

if [[ "$(base64 --version 2>&1)" = *"FreeBSD"* ]]; then
  B64FLAGS="--input"
else
  B64FLAGS="-w0"
fi

IMG_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH" 2>&1)

curl -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
    -H "x-goog-api-key: $GEMINI_API_KEY" \
    -H 'Content-Type: application/json' \
    -d "{
      \"contents\": [{
        \"parts\":[
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG_BASE64\"
              }
            },
            {\"text\": \"Transform the provided photograph of a modern city street at night into the artistic style of Vincent van Gogh's 'Starry Night'. Preserve the original composition of buildings and cars, but render all elements with swirling, impasto brushstrokes and a dramatic palette of deep blues and bright yellows.\"}
        ]
      }]
    }"  \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > city_style_transfer.png

```




* Input:                                         A photorealistic, high-resolution photograph of a busy city street...                    
  * Output:                                         Transform the provided photograph of a modern city street at night...                    


#### 4\. Advanced composition: Combining multiple images

Provide multiple images as context to create a new, composite scene. This is perfect for product mockups or creative collages.

### Template

```
Create a new image by combining the elements from the provided images. Take
the [element from image 1] and place it with/on the [element from image 2].
The final image should be a [description of the final scene].

```


### Prompt

```
"Create a professional e-commerce fashion photo. Take the blue floral dress
from the first image and let the woman from the second image wear it.
Generate a realistic, full-body shot of the woman wearing the dress, with
the lighting and shadows adjusted to match the outdoor environment."

```



### REST

```
IMG_PATH1=/path/to/your/dress.png
IMG_PATH2=/path/to/your/model.png

if [[ "$(base64 --version 2>&1)" = *"FreeBSD"* ]]; then
  B64FLAGS="--input"
else
  B64FLAGS="-w0"
fi

IMG1_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH1" 2>&1)
IMG2_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH2" 2>&1)

curl -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
    -H "x-goog-api-key: $GEMINI_API_KEY" \
    -H 'Content-Type: application/json' \
    -d "{
      \"contents\": [{
        \"parts\":[
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG1_BASE64\"
              }
            },
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG2_BASE64\"
              }
            },
            {\"text\": \"Create a professional e-commerce fashion photo. Take the blue floral dress from the first image and let the woman from the second image wear it. Generate a realistic, full-body shot of the woman wearing the dress, with the lighting and shadows adjusted to match the outdoor environment.\"}
        ]
      }]
    }"  \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > fashion_ecommerce_shot.png

```




* Input 1:                                         A professionally shot photo of a blue floral summer dress...                    
  * Input 2:                                         Full-body shot of a woman with her hair in a bun...                    
  * Output:                                         Create a professional e-commerce fashion photo...                    


#### 5\. High-fidelity detail preservation

To ensure critical details (like a face or logo) are preserved during an edit, describe them in great detail along with your edit request.

### Template

```
Using the provided images, place [element from image 2] onto [element from
image 1]. Ensure that the features of [element from image 1] remain
completely unchanged. The added element should [description of how the
element should integrate].

```


### Prompt

```
"Take the first image of the woman with brown hair, blue eyes, and a neutral
expression. Add the logo from the second image onto her black t-shirt.
Ensure the woman's face and features remain completely unchanged. The logo
should look like it's naturally printed on the fabric, following the folds
of the shirt."

```



### REST

```
IMG_PATH1=/path/to/your/woman.png
IMG_PATH2=/path/to/your/logo.png

if [[ "$(base64 --version 2>&1)" = *"FreeBSD"* ]]; then
  B64FLAGS="--input"
else
  B64FLAGS="-w0"
fi

IMG1_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH1" 2>&1)
IMG2_BASE64=$(base64 "$B64FLAGS" "$IMG_PATH2" 2>&1)

curl -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent" \
    -H "x-goog-api-key: $GEMINI_API_KEY" \
    -H 'Content-Type: application/json' \
    -d "{
      \"contents\": [{
        \"parts\":[
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG1_BASE64\"
              }
            },
            {
              \"inline_data\": {
                \"mime_type\":\"image/png\",
                \"data\": \"$IMG2_BASE64\"
              }
            },
            {\"text\": \"Take the first image of the woman with brown hair, blue eyes, and a neutral expression. Add the logo from the second image onto her black t-shirt. Ensure the woman's face and features remain completely unchanged. The logo should look like it's naturally printed on the fabric, following the folds of the shirt.\"}
        ]
      }]
    }"  \
  | grep -o '"data": "[^"]*"' \
  | cut -d'"' -f4 \
  | base64 --decode > woman_with_logo.png

```




* Input 1:                                         A professional headshot of a woman with brown hair and blue eyes...                    
  * Input 2:                                         A simple, modern logo with the letters 'G' and 'A'...                    
  * Output:                                         Take the first image of the woman with brown hair, blue eyes, and a neutral expression...                    


### Best Practices

To elevate your results from good to great, incorporate these professional strategies into your workflow.

*   **Be Hyper-Specific:** The more detail you provide, the more control you have. Instead of "fantasy armor," describe it: "ornate elven plate armor, etched with silver leaf patterns, with a high collar and pauldrons shaped like falcon wings."
*   **Provide Context and Intent:** Explain the _purpose_ of the image. The model's understanding of context will influence the final output. For example, "Create a logo for a high-end, minimalist skincare brand" will yield better results than just "Create a logo."
*   **Iterate and Refine:** Don't expect a perfect image on the first try. Use the conversational nature of the model to make small changes. Follow up with prompts like, "That's great, but can you make the lighting a bit warmer?" or "Keep everything the same, but change the character's expression to be more serious."
*   **Use Step-by-Step Instructions:** For complex scenes with many elements, break your prompt into steps. "First, create a background of a serene, misty forest at dawn. Then, in the foreground, add a moss-covered ancient stone altar. Finally, place a single, glowing sword on top of the altar."
*   **Use "Semantic Negative Prompts":** Instead of saying "no cars," describe the desired scene positively: "an empty, deserted street with no signs of traffic."
*   **Control the Camera:** Use photographic and cinematic language to control the composition. Terms like `wide-angle shot`, `macro shot`, `low-angle perspective`.

Limitations
-----------

*   For best performance, use the following languages: EN, es-MX, ja-JP, zh-CN, hi-IN.
*   Image generation does not support audio or video inputs.
*   The model won't always follow the exact number of image outputs that the user explicitly asks for.
*   The model works best with up to 3 images as an input.
*   When generating text for an image, Gemini works best if you first generate the text and then ask for an image with the text.
*   Uploading images of children is not currently supported in EEA, CH, and UK.
*   All generated images include a [SynthID watermark](https://ai.google.dev/responsible/docs/safeguards/synthid).

Optional configurations
-----------------------

You can optionally configure the response modalities and aspect ratio of the model's output in the `config` field of `generate_content` calls.

### Output types

The model defaults to returning text and image responses (i.e. `response_modalities=['Text', 'Image']`). You can configure the response to return only images without text using `response_modalities=['Image']`.


### REST

```
-d '{
  "contents": [{
    "parts": [
      {"text": "Create a picture of a nano banana dish in a fancy restaurant with a Gemini theme"}
    ]
  }],
  "generationConfig": {
    "responseModalities": ["Image"]
  }
}' \

```


### Aspect ratios

The model defaults to matching the output image size to that of your input image, or otherwise generates 1:1 squares. You can control the aspect ratio of the output image using the `aspect_ratio` field under `image_config` in the response request, shown here:


### REST

```
-d '{
  "contents": [{
    "parts": [
      {"text": "Create a picture of a nano banana dish in a fancy restaurant with a Gemini theme"}
    ]
  }],
  "generationConfig": {
    "imageConfig": {
      "aspectRatio": "16:9"
    }
  }
}' \

```


The different ratios available and the size of the image generated are listed in this table:


|Aspect ratio|Resolution|Tokens|
|------------|----------|------|
|1:1         |1024x1024 |1290  |
|2:3         |832x1248  |1290  |
|3:2         |1248x832  |1290  |
|3:4         |864x1184  |1290  |
|4:3         |1184x864  |1290  |
|4:5         |896x1152  |1290  |
|5:4         |1152x896  |1290  |
|9:16        |768x1344  |1290  |
|16:9        |1344x768  |1290  |
|21:9        |1536x672  |1290  |


When to use Imagen
------------------

In addition to using Gemini's built-in image generation capabilities, you can also access [Imagen](https://ai.google.dev/gemini-api/docs/imagen), our specialized image generation model, through the Gemini API.



* Attribute: Strengths
  * Imagen: Most capable image generation model to date. Recommended for photorealistic images, sharper clarity, improved spelling and typography.
  * Gemini Native Image: Default recommendation.Unparalleled flexibility, contextual understanding, and simple, mask-free editing. Uniquely capable of multi-turn conversational editing.
* Attribute: Availability
  * Imagen: Generally available
  * Gemini Native Image: Preview (Production usage allowed)
* Attribute: Latency
  * Imagen: Low. Optimized for near-real-time performance.
  * Gemini Native Image: Higher. More computation is required for its advanced capabilities.
* Attribute: Cost
  * Imagen: Cost-effective for specialized tasks. $0.02/image to $0.12/image
  * Gemini Native Image: Token-based pricing. $30 per 1 million tokens for image output (image output tokenized at 1290 tokens per image flat, up to 1024x1024px)
* Attribute: Recommended tasks
  * Imagen:                   Image quality, photorealism, artistic detail, or specific styles (e.g., impressionism, anime) are top priorities.          Infusing branding, style, or generating logos and product designs.          Generating advanced spelling or typography.              
  * Gemini Native Image:                   Interleaved text and image generation to seamlessly blend text and images.          Combine creative elements from multiple images with a single prompt.          Make highly specific edits to images, modify individual elements with simple language commands, and iteratively work on an image.          Apply a specific design or texture from one image to another while preserving the original subject's form and details.              


Imagen 4 should be your go-to model starting to generate images with Imagen. Choose Imagen 4 Ultra for advanced use-cases or when you need the best image quality (note that can only generate one image at a time).

What's next
-----------

*   Find more examples and code samples in the [cookbook guide](https://colab.sandbox.google.com/github/google-gemini/cookbook/blob/main/quickstarts/Image_out.ipynb).
*   Check out the [Veo guide](https://ai.google.dev/gemini-api/docs/video) to learn how to generate videos with the Gemini API.
*   To learn more about Gemini models, see [Gemini models](https://ai.google.dev/gemini-api/docs/models/gemini).

Except as otherwise noted, the content of this page is licensed under the [Creative Commons Attribution 4.0 License](https://creativecommons.org/licenses/by/4.0/), and code samples are licensed under the [Apache 2.0 License](https://www.apache.org/licenses/LICENSE-2.0). For details, see the [Google Developers Site Policies](https://developers.google.com/site-policies). Java is a registered trademark of Oracle and/or its affiliates.

Last updated 2025-10-02 UTC.