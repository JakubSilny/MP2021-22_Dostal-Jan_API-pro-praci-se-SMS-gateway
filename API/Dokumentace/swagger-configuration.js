var spec =

{
  "openapi": "3.0.0",
  "info": {
    "description": "<p>This is documentation of API, which acts as SMS gateway.</p> <p>It provides features such as:</p>"
      + "<ul>"
      + "<li>automatic validation of SMS body (handling diacritics, unknown characters)</li>"
      + "<li>generating different types of error messages</li>"
      + "<li>summary statistics of sent SMS</li>"
      + "<li>state of particular SMS</li>"
      + "<li>state of SMS waiting in queue</li>"
      + "<li>sending SMS</li>"
      + "</ul>" 
      +"<p>API supports <b>CORS</b> (including preflight requests) and access to API is protected by unique <b>API key</b>.</p>",
    "version": "v3.2",
    "title": "SMS Gateway",
    "license": {
      "name": "Apache 2.0",
      "url": "http://www.apache.org/licenses/LICENSE-2.0.html"
    },
    "contact": {
      "name": "Lukas Stanislav (CLIQUO)",
      "email": "stanislav@cliquo.cz",
      "url": "https://www.cliquo.cz/kontakt"
    },
    "termsOfService": "http://swagger.io/terms/"
  },
  "servers": [
    {
      "description": "Domain hosting this API",
      "url": "https://liberec.cliquo.cz"
    }
  ],
  "paths": {
    "/smsgateway/api": {
      "post": {
        "tags": [
          "api"
        ],
        "description": "Attempts to send SMS with sender determined from API key.",
        "summary": "Send SMS to one recipient",
        "operationId": "sendMessage",
        "requestBody": {
          "required": true,
          "description": "It is recommended to write only non-diacritic characters into payload's text property. "
            + "Some characters inside text property might need to be escaped in order for request to be successful.",
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "number": {
                    "type": "string"
                  },
                  "text": {
                    "type": "string"
                  }
                }
              },
              "examples": {
                "Option 1 (with plus character)": {
                  "value": {
                    "number": "+420721125332",
                    "text": "Hello World"
                  }
                },
                "Option 2 (with two zeros)": {
                  "value": {
                    "number": "00420721125332",
                    "text": "Hello World"
                  }
                }
              }
            }
          }
        },
        "responses": {
          "201": {
            "description": "created",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "id": {
                      "type": "integer",
                      "format": "int32",
                      "example": 32
                    }
                  }
                }
              }
            }
          },
          "400": {
            "description": "Bad Request",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string"
                    }
                  }
                },
                "examples": {
                  "Option 1 (Invalid request body content type)": {
                    "value": {
                      "error": "Invalid request payload content type"
                    }
                  },
                  "Option 2 (Bad request body structure, must be structured like example schema)": {
                    "value": {
                      "error": "Bad payload structure, must be formatted in JSON"
                    }
                  },
                  "Option 3 (Request body properties are either missing or does not match example schema)": {
                    "value": {
                      "error": "Invalid number and text properties in payload"
                    }
                  },
                  "Option 4 (Request body text property must not exceed 160 characters)": {
                    "value": {
                      "error": "Text property length in payload is too long"
                    }
                  },
                  "Option 5 (Request body text property cannot be empty)": {
                    "value": {
                      "error": "Text property cannot be empty"
                    }
                  }
                }
              }
            }
          },
          "401": {
            "description": "Not authorized",
            "headers": {
              "WWW-Authenticate": {
                "schema": {
                  "type": "string",
                  "example": "apiKey"
                },
                "description": "Gives hint about how to authenticate"
              }
            },
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string"
                    }
                  }
                },
                "examples": {
                  "Option 1 (XXXX header not entered)": {
                    "value": {
                      "error": "Unauthorized"
                    }
                  },
                  "Option 2 (XXXX header exists, but value is invalid)": {
                    "value": {
                      "error": "Invalid API key in request header"
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    "/smsgateway/api/{smsId}/status": {
      "get": {
        "tags": [
          "api"
        ],
        "description": "Finds out, based on entered SMS id and API key, if SMS was already sent, is waiting in queue or something went wrong.",
        "summary": "Get status of my chosen SMS",
        "operationId": "getMessageStatus",
        "parameters": [
          {
            "name": "smsId",
            "in": "path",
            "description": "SMS identifier, is used to get status of message",
            "required": true,
            "schema": {
              "type": "integer",
              "minimum": 1
            }
          }
        ],
        "responses": {
          "200": {
            "description": "successful operation",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "id": {
                      "type": "string",
                      "example": "52"
                    },
                    "status": {
                      "type": "string",
                      "enum": ["SENT", "PENDING", "CANCELED", "ERROR"]
                    },
                    "sent": {
                      "type": "string",
                      "example": "2021-06-30 18:30:20"
                    },
                    "": {
                      "type": "string",
                      "example": "2021-06-30 17:30:10"
                    }
                  }
                }
              }
            }
          },
          "400": {
            "description": "Bad request",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string",
                      "example": "Badly entered smsId parameter in url path"
                    }
                  }
                }
              }
            }
          },
          "404": {
            "description": "Not found",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string",
                      "example": "SMS was not found"
                    }
                  }
                }
              }
            }
          },
          "401": {
            "description": "Not authorized",
            "headers": {
              "WWW-Authenticate": {
                "schema": {
                  "type": "string",
                  "example": "apiKey"
                },
                "description": "Gives hint about how to authenticate"
              }
            },
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string"
                    }
                  }
                },
                "examples": {
                  "Option 1 (XXXX header not entered)": {
                    "value": {
                      "error": "Unauthorized"
                    }
                  },
                  "Option 2 (XXXX header exists, but value is invalid)": {
                    "value": {
                      "error": "Invalid API key in request header"
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    "/smsgateway/api/queue": {
      "get": {
        "tags": [
          "api"
        ],
        "description": "Finds all SMS, that are currently in queue, based on entered API key.",
        "summary": "Get all my sms waiting in queue",
        "operationId": "getMessageQueue",
        "responses": {
          "200": {
            "description": "successful operation",
            "content": {
              "application/json": {
                "schema": {
                  "type": "array",
                  "items": {
                    "type": "object",
                    "properties": {
                      "id": {
                        "type": "string",
                        "example": "30"
                      },
                      "text": {
                        "type": "string",
                        "example": "Ahoj"
                      },
                      "status": {
                        "type": "string",
                        "enum": ["PENDING", "SENT", "CANCELED", "ERROR"]
                      },
                      "number": {
                        "type": "string",
                        "example": "00420602343893"
                      },
                      "created": {
                        "type": "string",
                        "example": "2021-06-30 18:30:02"
                      },
                      "sent": {
                        "type": "string",
                        "example": "2021-06-30 19:30:02"
                      }
                    }
                  }
                }
              }
            }
          },
          "401": {
            "description": "Not authorized",
            "headers": {
              "WWW-Authenticate": {
                "schema": {
                  "type": "string",
                  "example": "apiKey"
                },
                "description": "Gives hint about how to authenticate"

              }
            },
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string"
                    }
                  }
                },
                "examples": {
                  "Option 1 (XXXX header not entered)": {
                    "value": {
                      "error": "Unauthorized"
                    }
                  },
                  "Option 2 (XXXX header exists, but value is invalid)": {
                    "value": {
                      "error": "Invalid API key in request header"
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    "/smsgateway/api/stats": {
      "get": {
        "tags": [
          "api"
        ],
        "description": "Finds all days from now to prior year, where at least one SMS was sent, and counts number of SMS sent per individual days "
          + "based on entered API key.",
        "summary": "Get count of my sent sms per individual days",
        "operationId": "getMessageStats",
        "responses": {
          "200": {
            "description": "successful operation",
            "content": {
              "application/json": {
                "schema": {
                  "type": "array",
                  "items": {
                    "type": "object",
                    "properties": {
                      "dateOfSending": {
                        "type": "string",
                        "example": "2021-06-30"
                      },
                      "numberOfSentSms": {
                        "type": "string",
                        "example": "332"
                      }
                    }
                  }
                }
              }
            }
          },
          "401": {
            "description": "Not authorized",
            "headers": {
              "WWW-Authenticate": {
                "schema": {
                  "type": "string",
                  "example": "apiKey"
                },
                "description": "Gives hint about how to authenticate"
              }
            },
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": {
                      "type": "string"
                    }
                  }
                },
                "examples": {
                  "Option 1 (XXXX header not entered)": {
                    "value": {
                      "error": "Unauthorized"
                    }
                  },
                  "Option 2 (XXXX header exists, but value is invalid)": {
                    "value": {
                      "error": "Invalid API key in request header"
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  },
  "components": {
    "securitySchemes": {
      "ApiKeyAuth": {
        "type": "apiKey",
        "name": "XXXX",
        "in": "header"
      }
    }
  },
  "externalDocs": {
    "description": "Find out more about Swagger",
    "url": "http://swagger.io"
  },
  "security": [
    {
      "ApiKeyAuth": []
    }
  ]
}