# Sentinel API Response Standard

## Success Response

```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {}
}
```

## Error Response

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field_name": [
      "Error detail"
    ]
  }
}
```

## Rules

- All endpoints must return `success`, `message`, and either `data` or `errors`.
- Validation failures return field-level arrays in `errors`.
- External identifiers in API payloads should be UUID values, not numeric primary keys.
- Authentication and authorization failures must use consistent error structures.
