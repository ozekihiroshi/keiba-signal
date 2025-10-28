import sys, pandas as pd
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.metrics import roc_auc_score

csv = sys.argv[1]
df = pd.read_csv(csv)
df = df.dropna()  # 簡易
X = df[["distance","surface_TURF","surface_DIRT","is_favorite"]]
y = (df["finish"].fillna(0)).astype(int)  # 仮

if len(df) < 10:
    print("not enough data")
    sys.exit(0)

Xtr, Xte, ytr, yte = train_test_split(X,y,test_size=0.2,random_state=42)
m = LogisticRegression(max_iter=1000).fit(Xtr,ytr)
proba = m.predict_proba(Xte)[:,1]
print("AUC:", roc_auc_score(yte, proba))
print("head proba:", proba[:5])
