#!/bin/bash

# Rate Limiting Test Script
# Tests the /login route rate limiting (5 attempts per 15 minutes)

echo "=========================================="
echo "Rate Limiting Test for /login Route"
echo "=========================================="
echo ""
echo "Configuration:"
echo "  - Max Attempts: 5"
echo "  - Time Window: 15 minutes"
echo "  - Route: /login"
echo ""
echo "Testing with 7 rapid login attempts..."
echo ""

BASE_URL="http://localhost:8080"
LOGIN_URL="${BASE_URL}/login"

# Counter for successful requests
SUCCESS_COUNT=0
RATE_LIMITED=0

for i in {1..7}; do
    echo "----------------------------------------"
    echo "Attempt #$i"
    echo "----------------------------------------"
    
    # Make POST request to login
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$LOGIN_URL" \
        -d "email=test@example.com" \
        -d "password=wrongpassword" \
        -H "Content-Type: application/x-www-form-urlencoded")
    
    # Extract HTTP status code (last line)
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | head -n-1)
    
    echo "HTTP Status: $HTTP_CODE"
    
    if [ "$HTTP_CODE" == "429" ]; then
        echo "✅ Rate limit triggered (as expected)"
        RATE_LIMITED=$((RATE_LIMITED + 1))
        echo "Response: $BODY"
    elif [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "302" ]; then
        echo "✅ Request allowed (attempt $i of 5)"
        SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
    else
        echo "⚠️  Unexpected status code: $HTTP_CODE"
    fi
    
    echo ""
    sleep 1
done

echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo "Successful requests: $SUCCESS_COUNT"
echo "Rate limited: $RATE_LIMITED"
echo ""

if [ $SUCCESS_COUNT -eq 5 ] && [ $RATE_LIMITED -ge 2 ]; then
    echo "✅ TEST PASSED"
    echo "Rate limiting is working correctly!"
    echo "- First 5 attempts were allowed"
    echo "- Attempts 6+ were rate limited (HTTP 429)"
else
    echo "❌ TEST FAILED"
    echo "Expected: 5 successful, 2 rate limited"
    echo "Got: $SUCCESS_COUNT successful, $RATE_LIMITED rate limited"
fi

echo ""
echo "To reset rate limits:"
echo "  rm -rf var/cache/rate_limit/*"
echo ""
