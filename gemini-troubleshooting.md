# Troubleshooting guide  |  Gemini API  |  Google AI for Developers
Use this guide to help you diagnose and resolve common issues that arise when you call the Gemini API. You may encounter issues from either the Gemini API backend service or the client SDKs. Our client SDKs are open sourced in the following repositories:

*   [python-genai](https://github.com/googleapis/python-genai)
*   [js-genai](https://github.com/googleapis/js-genai)
*   [go-genai](https://github.com/googleapis/go-genai)

If you encounter API key issues, verify that you have set up your API key correctly per the [API key setup guide](https://ai.google.dev/gemini-api/docs/api-key).

Gemini API backend service error codes
--------------------------------------

The following table lists common backend error codes you may encounter, along with explanations for their causes and troubleshooting steps:



* HTTP Code   :    400   
  * Status   : INVALID_ARGUMENT
  * Description   : The request body is malformed.
  * Example   : There is a typo, or a missing required field in your request.
  * Solution   : Check the API reference for request format, examples, and supported versions. Using features from a newer API version with an older endpoint can cause errors.
* HTTP Code   :    400   
  * Status   : FAILED_PRECONDITION
  * Description   : Gemini API free tier is not available in your country. Please enable billing on your project in Google AI Studio.
  * Example   : You are making a request in a region where the free tier is not supported, and you have not enabled billing on your project in Google AI Studio.
  * Solution   : To use the Gemini API, you will need to setup a paid plan using Google AI Studio.
* HTTP Code   :    403   
  * Status   : PERMISSION_DENIED
  * Description   : Your API key doesn't have the required permissions.
  * Example   : You are using the wrong API key;  you    are trying to use a tuned model without going through proper authentication.
  * Solution   : Check that your API key is set and has the right access. And make sure to go through proper authentication to use tuned models.
* HTTP Code   :     404    
  * Status   : NOT_FOUND
  * Description   : The requested resource wasn't found.
  * Example   : An image, audio, or video file referenced in your request was not found.
  * Solution   : Check if all parameters in your request are valid for your API version.
* HTTP Code   :    429   
  * Status   : RESOURCE_EXHAUSTED
  * Description   : You've exceeded the rate limit.
  * Example   : You are sending too many requests per minute with the free tier Gemini API.
  * Solution   : Verify that you're within the model's rate limit. Request a quota increase if needed.
* HTTP Code   :    500   
  * Status   : INTERNAL
  * Description   : An unexpected error occurred on Google's side.
  * Example   : Your input context is too long.
  * Solution   : Reduce your input context or temporarily switch to another model (e.g. from Gemini 1.5 Pro to Gemini 1.5 Flash) and see if it works. Or wait a bit and retry your request. If the issue persists after retrying, please report it using the Send feedback button in Google AI Studio.
* HTTP Code   :    503   
  * Status   : UNAVAILABLE
  * Description   : The service may be temporarily overloaded or down.
  * Example   : The service is temporarily running out of capacity.
  * Solution   : Temporarily switch to another model (e.g. from Gemini 1.5 Pro to Gemini 1.5 Flash) and see if it works. Or wait a bit and retry your request. If the issue persists after retrying, please report it using the Send feedback button in Google AI Studio.
* HTTP Code   :    504   
  * Status   : DEADLINE_EXCEEDED
  * Description   : The service is unable to finish processing within the deadline.
  * Example   : Your prompt (or context) is too large to be processed in time.
  * Solution   : Set a larger 'timeout' in your client request to avoid this error.


Check your API calls for model parameter errors
-----------------------------------------------

Verify that your model parameters are within the following values:



* Model parameter   :    Candidate count   
  * Values (range)   : 1-8 (integer)
* Model parameter   :    Temperature   
  * Values (range)   : 0.0-1.0
* Model parameter   :     Max output tokens    
  * Values (range)   :     Use    get_model (Python)    to determine the maximum number of tokens for the model you are using.    
* Model parameter   :    TopP   
  * Values (range)   : 0.0-1.0


In addition to checking parameter values, make sure you're using the correct [API version](https://ai.google.dev/gemini-api/docs/api-versions) (e.g., `/v1` or `/v1beta`) and model that supports the features you need. For example, if a feature is in Beta release, it will only be available in the `/v1beta` API version.

Check if you have the right model
---------------------------------

Verify that you are using a supported model listed on our [models page](https://ai.google.dev/gemini-api/docs/models/gemini).

Higher latency or token usage with 2.5 models
---------------------------------------------

If you're observing higher latency or token usage with the 2.5 Flash and Pro models, this can be because they come with **thinking is enabled by default** in order to enhance quality. If you are prioritizing speed or need to minimize costs, you can adjust or disable thinking.

Refer to [thinking page](about:/gemini-api/docs/thinking#set-budget) for guidance and sample code.

Safety issues
-------------

If you see a prompt was blocked because of a safety setting in your API call, review the prompt with respect to the filters you set in the API call.

If you see `BlockedReason.OTHER`, the query or response may violate the [terms of service](https://ai.google.dev/terms) or be otherwise unsupported.

Recitation issue
----------------

If you see the model stops generating output due to the RECITATION reason, this means the model output may resemble certain data. To fix this, try to make prompt / context as unique as possible and use a higher temperature.

Repetitive tokens issue
-----------------------

If you see repeated output tokens, try the following suggestions to help reduce or eliminate them.



* Description: Repeated hyphens in Markdown tables
  * Cause:     This can occur when the contents of the table are long as the model tries    to create a visually aligned Markdown table. However, the alignment in    Markdown is not necessary for correct rendering.    
  * Suggested workaround:               Add instructions in your prompt to give the model specific guidelines        for generating Markdown tables. Provide examples that follow those        guidelines. You can also try adjusting the temperature. For generating        code or very structured output like Markdown tables,        high temperature have shown to work better (>= 0.8).               The following is an example set of guidelines you can add to your        prompt to prevent this issue:                urltomarkdowncodeblockplaceholder00.13036407658196136    
* Description:       Repeated tokens in Markdown tables    
  * Cause:       Similar to the repeated hyphens, this occurs when the model tries to      visually align the contents of the table. The alignment in Markdown is      not required for correct rendering.    
  * Suggested workaround:                         Try adding instructions like the following to your system prompt:          urltomarkdowncodeblockplaceholder10.3263157398567271                          Try adjusting the temperature. Higher temperatures (>= 0.8)          generally helps to eliminate repetitions or duplication in          the output.                  
* Description:       Repeated newlines (\n) in structured output    
  * Cause:       When the model input contains unicode or escape sequences like      \u or \t, it can lead to repeated newlines.    
  * Suggested workaround:                         Check for and replace forbidden escape sequences with UTF-8 characters          in your prompt. For example, \u          escape sequence in your JSON examples can cause the model to use them          in its output too.                          Instruct the model on allowed escapes. Add a system instruction like          this:          urltomarkdowncodeblockplaceholder20.13142208170437342                  
* Description:       Repeated text in using structured output    
  * Cause:       When the model output has a different order for the fields than the      defined structured schema, this can lead to repeating text.    
  * Suggested workaround:                         Don't specify the order of fields in your prompt.                          Make all output fields required.                  
* Description:       Repetitive tool calling    
  * Cause:       This can occur if the model loses the context of previous thoughts and/or      call an unavailable endpoint that it's forced to.    
  * Suggested workaround:       Instruct the model to maintain state within its thought process.      Add this to the end of your system instructions:      urltomarkdowncodeblockplaceholder30.5343775725364417    
* Description:       Repetitive text that's not part of structured output    
  * Cause:       This can occur if the model gets stuck on a request that it can't resolve.    
  * Suggested workaround:                         If thinking is turned on, avoid giving explicit orders for how to          think through a problem in the instructions. Just ask for the final          output.                          Try a higher temperature >= 0.8.                          Add instructions like "Be concise", "Don't repeat yourself", or          "Provide the answer once".                  


Improve model output
--------------------

For higher quality model outputs, explore writing more structured prompts. The [prompt engineering guide](https://ai.google.dev/gemini-api/docs/prompting-strategies) page introduces some basic concepts, strategies, and best practices to get you started.

Understand token limits
-----------------------

Read through our [Token guide](https://ai.google.dev/gemini-api/docs/tokens) to better understand how to count tokens and their limits.

Known issues
------------

*   The API supports only a number of select languages. Submitting prompts in unsupported languages can produce unexpected or even blocked responses. See [available languages](about:/gemini-api/docs/models#supported-languages) for updates.

File a bug
----------

Join the discussion on the [Google AI developer forum](https://discuss.ai.google.dev/) if you have questions.